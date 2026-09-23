<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceRequest;
use App\Models\Payment;
use App\Models\ProjectBudget;
use App\Models\Reimbursement;
use App\Services\AccessService;
use App\Services\ApprovalEngine;
use App\Services\AutomationLogger;
use App\Services\ZohoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:finance.view');
    }

    public function dashboard(): View
    {
        $canSeeValues = AccessService::can('finance.value.view');

        return view('finance.dashboard', [
            'canSeeValues' => $canSeeValues,
            'invoiceCounts' => Invoice::query()->selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status'),
            'receivable' => $canSeeValues ? Invoice::query()->whereNotIn('status', ['Lunas'])->sum('nominal') : null,
            'paymentCounts' => Payment::query()->selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status'),
            'reimbCounts' => Reimbursement::query()->selectRaw('approval_status, count(*) total')->groupBy('approval_status')->pluck('total', 'approval_status'),
            'aging' => $canSeeValues ? Invoice::query()
                ->whereNotIn('status', ['Lunas'])->whereNotNull('due_date')
                ->get()->groupBy(function (Invoice $invoice) {
                    $days = now()->startOfDay()->diffInDays($invoice->due_date, false);

                    return $days < 0 ? 'Overdue' : ($days <= 7 ? 'Due ≤ 7 hari' : 'OK');
                })->map->count() : collect(),
            'latestInvoices' => Invoice::query()->with('client')->latest()->limit(5)->get(),
            'latestReimbursements' => Reimbursement::query()->with('employee')->latest()->limit(5)->get(),
        ]);
    }

    // ─── Invoice requests ────────────────────────────────────────────────
    public function invoiceRequests(): View
    {
        return view('finance.invoice-requests', [
            'requests' => InvoiceRequest::query()->with(['requester', 'project', 'client'])->latest()->paginate(15),
            'projects' => \App\Models\Project::query()->whereNull('archived_at')->orderBy('project_name')->get(),
        ]);
    }

    public function storeInvoiceRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'requester_employee_id' => ['required', 'exists:employees,employee_id'],
            'project_id' => ['required', 'exists:projects,project_id'],
            'client_id' => ['required', 'exists:clients,client_id'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
        ]);

        $record = InvoiceRequest::create($data + ['approval_status' => 'Diajukan', 'created_by' => AccessService::employeeId()]);

        $requester = Employee::query()->whereKey($data['requester_employee_id'])->first();
        $chain = ApprovalEngine::chainForEmployee($requester);
        if (! $chain) {
            return back()->withErrors(['requester_employee_id' => 'Requester tidak memiliki atasan untuk approval.']);
        }
        $approval = ApprovalEngine::submit('invoice_request', $record->invoice_request_id, $data['requester_employee_id'], $chain);
        $record->update(['approval_request_id' => $approval->request_id]);

        AutomationLogger::log('invoice_request_submitted', 'invoice_request', $record->invoice_request_id, 'success');

        return redirect()->route('finance.invoice-requests')->with('status', 'Invoice request diajukan dan menunggu approval.');
    }

    // ─── Invoices (Zoho generator) ────────────────────────────────────────
    public function invoices(): View
    {
        return view('finance.invoices', [
            'invoices' => Invoice::query()->with(['client', 'project'])->latest()->paginate(15),
            'approvedRequests' => InvoiceRequest::query()->where('approval_status', 'Disetujui')
                ->whereDoesntHave('invoices')
                ->with(['requester', 'project', 'client'])->get(),
            'canSeeValues' => AccessService::can('finance.value.view'),
        ]);
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invoice_request_id' => ['nullable', 'exists:invoice_requests,invoice_request_id'],
            'client_id' => ['required', 'exists:clients,client_id'],
            'project_id' => ['nullable', 'exists:projects,project_id'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ]);

        $invoice = Invoice::create($data + ['status' => 'Menunggu Pelunasan', 'created_by' => AccessService::employeeId()]);

        // ERP → Zoho: generate invoice document (skipped gracefully if unconfigured).
        $zohoId = app(ZohoService::class)->createInvoice($invoice);
        AutomationLogger::log('zoho_invoice_create', 'invoice', $invoice->invoice_id, $zohoId ? 'success' : 'skipped');

        return redirect()->route('finance.invoices')->with('status', 'Invoice dibuat.'.($zohoId ? ' Tersinkron ke Zoho.' : ''));
    }

    public function syncInvoice(Request $request, Invoice $invoice): RedirectResponse
    {
        $result = app(ZohoService::class)->syncInvoiceStatus($invoice, $request->input('status'));
        AutomationLogger::log('zoho_invoice_sync', 'invoice', $invoice->invoice_id, 'success');

        return back()->with('status', 'Status invoice disinkronkan'.($result ? ' (Zoho: '.($result['zoho_status'] ?? '-').').' : '.'));
    }

    public function updateInvoiceStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->update(['status' => $request->string('status')->toString()]);

        return back()->with('status', 'Status invoice diperbarui.');
    }

    // ─── Payments ─────────────────────────────────────────────────────────
    public function payments(): View
    {
        return view('finance.payments', [
            'payments' => Payment::query()->with(['invoice', 'pic'])->latest()->paginate(15),
            'invoices' => Invoice::query()->orderByDesc('created_at')->limit(50)->get(),
            'canSeeValues' => AccessService::can('finance.value.view'),
        ]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invoice_id' => ['nullable', 'exists:invoices,invoice_id'],
            'vendor_or_recipient' => ['required', 'string', 'max:200'],
            'pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        Payment::create($data + [
            'status' => 'Diajukan',
            'pic_employee_id' => $data['pic_employee_id'] ?? AccessService::employeeId(),
            'created_by' => AccessService::employeeId(),
        ]);

        return redirect()->route('finance.payments')->with('status', 'Payment diajukan.');
    }

    public function updatePaymentStatus(Request $request, Payment $payment): RedirectResponse
    {
        $payment->update([
            'status' => $request->string('status')->toString(),
            'paid_at' => in_array($request->string('status')->toString(), ['Lunas', 'DP', 'Sebagian'], true) ? now() : $payment->paid_at,
        ]);

        return back()->with('status', 'Status payment diperbarui.');
    }

    // ─── Reimbursements ───────────────────────────────────────────────────
    public function reimbursements(): View
    {
        return view('finance.reimbursements', [
            'reimbursements' => Reimbursement::query()->with('employee')->latest()->paginate(15),
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(),
            'canSeeValues' => AccessService::can('finance.value.view'),
        ]);
    }

    public function storeReimbursement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,employee_id'],
            'date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'evidence' => ['required', 'string', 'max:2000'],     // evidence is mandatory
            'notes' => ['nullable', 'string'],
        ]);

        $record = Reimbursement::create($data + ['approval_status' => 'Diajukan', 'created_by' => AccessService::employeeId()]);

        $requester = Employee::query()->whereKey($data['employee_id'])->first();
        $chain = ApprovalEngine::chainForEmployee($requester);
        if ($chain) {
            $approval = ApprovalEngine::submit('reimbursement', $record->reimbursement_id, $data['employee_id'], $chain);
            $record->update(['approval_request_id' => $approval->request_id]);
        }

        return redirect()->route('finance.reimbursements')->with('status', 'Reimbursement diajukan dengan evidence.');
    }

    public function updateReimbursementStatus(Request $request, Reimbursement $reimbursement): RedirectResponse
    {
        $status = $request->string('status')->toString();
        $reimbursement->update([
            'approval_status' => $status,
            'paid_at' => $status === 'Dibayarkan' ? now() : $reimbursement->paid_at,
        ]);

        AutomationLogger::log('reimbursement_status', 'reimbursement', $reimbursement->reimbursement_id, 'success');

        return back()->with('status', 'Status reimbursement diperbarui.');
    }

    // ─── Project budgets ──────────────────────────────────────────────────
    public function budgets(): View
    {
        return view('finance.budgets', [
            'budgets' => ProjectBudget::query()->with('project.client')->latest()->paginate(15),
            'canSeeValues' => AccessService::can('finance.value.view'),
        ]);
    }
}