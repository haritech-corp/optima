<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lead;
use App\Services\AccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:crm.lead.view')->only(['index', 'show']);
        $this->middleware('permission:crm.lead.create')->only(['create', 'store']);
        $this->middleware('permission:crm.lead.edit')->only(['edit', 'update']);
        $this->middleware('permission:crm.lead.archive')->only(['archive']);
    }

    public function index(Request $request): View
    {
        $query = Lead::query()->with('pic')->whereNull('archived_at');

        if (AccessService::moduleScope('crm') === 'own') {
            $query->where('pic_employee_id', AccessService::employeeId());
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('name', 'ilike', "%{$search}%")
                ->orWhere('company_or_community', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%"));
        }
        foreach (['status', 'sector', 'source'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->string($filter)->toString());
            }
        }

        return view('leads.index', [
            'leads' => $query->latest()->paginate(15)->withQueryString(),
            'statusOptions' => \App\Models\StatusMaster::labels('lead_status'),
        ]);
    }

    public function create(): View
    {
        return view('leads.form', ['lead' => new Lead]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['pic_employee_id'] ??= AccessService::employeeId();
        $data['created_by'] = AccessService::employeeId();
        $data['normalized_email'] = strtolower((string) ($data['email'] ?? ''));
        $data['normalized_phone'] = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));

        $duplicate = Lead::query()->whereNull('archived_at')
            ->where(fn ($q) => $q->when($data['normalized_email'], fn ($x) => $x->orWhere('normalized_email', $data['normalized_email']))
                ->when($data['normalized_phone'], fn ($x) => $x->orWhere('normalized_phone', $data['normalized_phone'])))
            ->exists();

        if ($duplicate && ! $request->boolean('confirm_duplicate')) {
            return back()->withInput()->with('duplicate_warning', true);
        }

        $lead = Lead::create($data);

        return redirect()->route('leads.show', $lead)->with('status', 'Lead berhasil dibuat.');
    }

    public function show(Lead $lead): View
    {
        $lead->load(['pic', 'interactions.pic', 'followUps.assignee', 'client']);

        return view('leads.show', compact('lead'));
    }

    public function edit(Lead $lead): View
    {
        return view('leads.form', compact('lead'));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $this->validated($request);
        $data['normalized_email'] = strtolower((string) ($data['email'] ?? ''));
        $data['normalized_phone'] = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
        $lead->update($data);

        return redirect()->route('leads.show', $lead)->with('status', 'Lead berhasil diperbarui.');
    }

    public function archive(Lead $lead): RedirectResponse
    {
        $lead->update(['archived_at' => now()]);

        return redirect()->route('leads.index')->with('status', 'Lead diarsipkan.');
    }

    public function convert(Request $request, Lead $lead): RedirectResponse
    {
        $client = DB::transaction(function () use ($lead): Client {
            $client = Client::create([
                'name' => $lead->company_or_community ?: $lead->name,
                'legal_name' => $lead->company_or_community,
                'sector' => $lead->sector,
                'owner_employee_id' => $lead->pic_employee_id,
                'status' => 'active',
                'created_by' => AccessService::employeeId(),
            ]);
            $lead->update(['status' => 'converted', 'client_id' => $client->client_id, 'converted_at' => now()]);

            return $client;
        });

        return redirect()->route('clients.show', $client)->with('status', 'Lead berhasil dikonversi menjadi client.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company_or_community' => ['nullable', 'string', 'max:200'],
            'sector' => ['nullable', 'string', 'max:80'],
            'source' => ['required', 'string', 'max:80'],
            'pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', 'string'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}