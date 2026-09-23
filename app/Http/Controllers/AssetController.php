<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetBooking;
use App\Models\AssetConditionLog;
use App\Models\InventoryCheck;
use App\Services\AccessService;
use App\Services\AuditLogger;
use App\Services\AutomationLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory.view')->only(['index', 'show']);
        $this->middleware('permission:inventory.create')->only(['create', 'store']);
        $this->middleware('permission:inventory.edit')->only(['edit', 'update', 'condition']);
        $this->middleware('permission:inventory.book')->only(['book', 'approveBooking']);
        $this->middleware('permission:inventory.opname')->only(['opnames', 'storeOpname', 'completeOpname']);
    }

    public function index(Request $request): View
    {
        $query = Asset::query()->whereNull('archived_at');

        if ($request->filled('asset_group')) {
            $query->where('asset_group', $request->string('asset_group')->toString());
        }
        foreach (['status', 'condition', 'location', 'platform'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->string($filter)->toString());
            }
        }

        return view('assets.index', [
            'assets' => $query->latest()->paginate(15)->withQueryString(),
            'group' => $request->string('asset_group')->toString(),
            'groupCounts' => Asset::query()->whereNull('archived_at')->selectRaw('asset_group, count(*) total')->groupBy('asset_group')->pluck('total', 'asset_group'),
            'availableCount' => Asset::query()->whereNull('archived_at')->where('status', 'Available')->where('condition', 'Baik')->count(),
            'maintenanceCount' => Asset::query()->whereNull('archived_at')->whereIn('status', ['Maintenance', 'Rusak'])->count(),
        ]);
    }

    public function create(): View
    {
        return view('assets.form', [
            'asset' => new Asset,
            'employees' => \App\Models\Employee::query()->where('status', 'active')->orderBy('name')->get(),
            'clients' => \App\Models\Client::query()->whereNull('archived_at')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $asset = Asset::create($this->validated($request));

        return redirect()->route('assets.show', $asset)->with('status', 'Aset berhasil didaftarkan.');
    }

    public function show(Asset $asset): View
    {
        $asset->load(['bookings.requester', 'client', 'campaign', 'holder', 'pic', 'conditionLogs']);

        return view('assets.show', [
            'asset' => $asset,
            'projects' => \App\Models\Project::query()->whereNull('archived_at')->whereNotIn('overall_status', ['Cancelled', 'Closed'])->orderBy('project_name')->get(),
            'employees' => \App\Models\Employee::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function edit(Asset $asset): View
    {
        return view('assets.form', [
            'asset' => $asset,
            'employees' => \App\Models\Employee::query()->where('status', 'active')->orderBy('name')->get(),
            'clients' => \App\Models\Client::query()->whereNull('archived_at')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $data = $this->validated($request);

        // Condition changes are stored with date, PIC, old and new condition.
        if ($data['condition'] !== $asset->condition) {
            AssetConditionLog::create([
                'asset_id' => $asset->asset_id,
                'changed_by_employee_id' => AccessService::employeeId(),
                'change_date' => today(),
                'old_condition' => $asset->condition,
                'new_condition' => $data['condition'],
                'notes' => $request->input('condition_note'),
            ]);
            if ($data['condition'] !== 'Baik') {
                $data['status'] = 'Maintenance';
            }
            AuditLogger::statusChange('asset', $asset->asset_id, $asset->condition, $data['condition'], AccessService::employeeId());
        }

        $asset->update($data);

        return redirect()->route('assets.show', $asset)->with('status', 'Aset berhasil diperbarui.');
    }

    public function book(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'project_id' => ['nullable', 'exists:projects,project_id'],
            'client_id' => ['nullable', 'exists:clients,client_id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // OOH booking must perform booking conflict checks; non-available
        // assets (Maintenance/Perlu Perbaikan/Rusak) cannot be booked.
        if (! $asset->isAvailableBetween(now()->parse($data['start_date']), now()->parse($data['end_date']))) {
            return back()->withErrors(['start_date' => 'Aset ini sudah dibooking atau tidak tersedia pada periode tersebut.'])->withInput();
        }

        AssetBooking::create($data + [
            'asset_id' => $asset->asset_id,
            'status' => 'Requested',
            'requested_by' => AccessService::employeeId(),
        ]);

        AutomationLogger::log('ooh_booking_request', 'asset', $asset->asset_id, 'success');

        return back()->with('status', 'Booking diajukan. Menunggu persetujuan.');
    }

    public function approveBooking(Request $request, AssetBooking $booking): RedirectResponse
    {
        $asset = $booking->asset;

        $conflict = AssetBooking::query()
            ->where('asset_id', $asset->asset_id)
            ->where('booking_id', '!=', $booking->booking_id)
            ->whereIn('status', ['Approved', 'Booked'])
            ->where(function ($q) use ($booking) {
                $q->whereDate('start_date', '<=', $booking->end_date->toDateString())
                    ->whereDate('end_date', '>=', $booking->start_date->toDateString());
            })
            ->exists();

        if ($conflict) {
            return back()->withErrors(['status' => 'Konflik booking terdeteksi: periode ini sudah dibooking aset lain.']);
        }

        $booking->update(['status' => 'Booked', 'approved_by' => AccessService::employeeId()]);
        $asset->update(['status' => 'Booked']);

        // Re-open rentals expire automatically via the automation runner.
        AutomationLogger::log('ooh_booking_approved', 'asset_booking', $booking->booking_id, 'success');

        return back()->with('status', 'Booking disetujui. Status aset menjadi Booked.');
    }

    public function cancelBooking(Request $request, AssetBooking $booking): RedirectResponse
    {
        $booking->update(['status' => 'Cancelled']);
        $booking->asset->update(['status' => 'Available']);

        return back()->with('status', 'Booking dibatalkan.');
    }

    public function condition(Request $request, Asset $asset): RedirectResponse
    {
        return $this->update($request, $asset);
    }

    public function opnames(Request $request): View
    {
        return view('assets.opnames', [
            'checks' => InventoryCheck::query()->with('pic')->latest()->paginate(15),
        ]);
    }

    public function storeOpname(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_group' => ['required', 'in:ooh,general,platform,vehicle'],
            'scheduled_date' => ['required', 'date'],
            'pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'notes' => ['nullable', 'string'],
        ]);

        InventoryCheck::create($data + ['status' => 'Scheduled']);

        return back()->with('status', 'Jadwal stock opname dibuat.');
    }

    public function completeOpname(Request $request, InventoryCheck $check): RedirectResponse
    {
        $data = $request->validate(['result' => ['nullable', 'string']]);
        $check->update(['status' => 'Done', 'executed_date' => today(), 'result' => $data['result'] ?? $check->result]);

        return back()->with('status', 'Stock opname selesai.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'asset_group' => ['required', 'in:ooh,general,platform,vehicle'],
            'name' => ['nullable', 'string', 'max:200'],
            'location' => ['nullable', 'string', 'max:200'],
            'media_type' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['required', 'in:Baik,Perlu Perbaikan,Rusak'],
            'status' => ['nullable', 'string', 'max:40'],
            'client_id' => ['nullable', 'exists:clients,client_id'],
            'rental_start' => ['nullable', 'date'],
            'rental_end' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:100'],
            'holder_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'procurement_date' => ['nullable', 'date'],
            'platform' => ['nullable', 'string', 'max:60'],
            'content_slot' => ['nullable', 'string', 'max:100'],
            'campaign_id' => ['nullable', 'string', 'max:40'],
            'type' => ['nullable', 'string', 'max:100'],
            'pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'related_document' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'condition_note' => ['nullable', 'string'],
        ]);
    }
}