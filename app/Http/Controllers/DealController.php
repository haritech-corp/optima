<?php

namespace App\Http\Controllers;

use App\Events\DealClosed;
use App\Models\Deal;
use App\Models\StatusMaster;
use App\Services\AccessService;
use App\Services\AutomationLogger;
use App\Services\WorkloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DealController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sales.pipeline.view')->only(['index', 'show']);
        $this->middleware('permission:sales.pipeline.create')->only(['create', 'store']);
        $this->middleware('permission:sales.pipeline.edit')->only(['edit', 'update', 'markLost', 'restore']);
        $this->middleware('permission:sales.pipeline.close')->only(['close']);
    }

    public function index(Request $request): View
    {
        $view = $request->string('view')->toString() ?: 'all';
        $query = Deal::query()->with(['client', 'pic', 'project'])->latest('last_update');

        if (AccessService::moduleScope('sales') === 'own') {
            $query->where('pic_employee_id', AccessService::employeeId());
        }

        switch ($view) {
            case 'mine':
                $query->where('pic_employee_id', AccessService::employeeId());
                break;
            case 'by_stage':
            case 'target_closing':
                $query->where('stage', 'Closing')->where('status', 'open');
                break;
            case 'won':
                $query->where('status', 'won');
                break;
            case 'lost_on_hold':
                $query->whereIn('status', ['lost', 'on_hold']);
                break;
            default:
                if ($request->filled('stage')) {
                    $query->where('stage', $request->string('stage')->toString());
                }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('pic_employee_id')) {
            $query->where('pic_employee_id', $request->string('pic_employee_id')->toString());
        }

        return view('sales.index', [
            'view' => $view,
            'deals' => $query->paginate(15)->withQueryString(),
            'stageOptions' => StatusMaster::labels('deal_stage'),
            'stageCounts' => Deal::query()
                ->when(AccessService::moduleScope('sales') === 'own', fn ($q) => $q->where('pic_employee_id', AccessService::employeeId()))
                ->selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total', 'stage'),
            'pipelineValue' => AccessService::can('finance.value.view')
                ? Deal::query()->where('status', 'open')->sum('deal_value')
                : null,
        ]);
    }

    public function create(): View
    {
        return view('sales.form', [
            'deal' => new Deal,
            'clients' => \App\Models\Client::query()->whereNull('archived_at')->orderBy('name')->get(),
            'employees' => \App\Models\Employee::query()->where('status', 'active')->orderBy('name')->get(),
            'services' => config('optima.service_lines'),
            'stageOptions' => StatusMaster::labels('deal_stage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['pic_employee_id'] ??= AccessService::employeeId();
        $data['created_by'] = AccessService::employeeId();
        $data['last_update'] = now();

        $deal = Deal::create($data);

        return redirect()->route('deals.show', $deal)->with('status', 'Deal berhasil dibuat.');
    }

    public function show(Deal $deal): View
    {
        $deal->load(['client', 'pic', 'project', 'interactions.pic']);

        return view('sales.show', ['deal' => $deal]);
    }

    public function edit(Deal $deal): View
    {
        return view('sales.form', [
            'deal' => $deal,
            'clients' => \App\Models\Client::query()->whereNull('archived_at')->orderBy('name')->get(),
            'employees' => \App\Models\Employee::query()->where('status', 'active')->orderBy('name')->get(),
            'services' => config('optima.service_lines'),
            'stageOptions' => StatusMaster::labels('deal_stage'),
        ]);
    }

    public function update(Request $request, Deal $deal): RedirectResponse
    {
        $data = $this->validated($request, $deal);

        if (isset($data['stage']) && $data['stage'] === Deal::CLOSED_STAGE) {
            // Closing must go through the explicit close action (automation).
            $data['stage'] = $deal->stage;
        }

        $deal->update($data + ['last_update' => now()]);

        return redirect()->route('deals.show', $deal)->with('status', 'Deal berhasil diperbarui.');
    }

    /**
     * Closing = review stage: validates minimum sales data, then triggers the
     * deal_closing automation (Project + Invoice Request) via the event bus.
     */
    public function close(Request $request, Deal $deal): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'in:1'],
        ]);

        if (empty($deal->service)) {
            return back()->withErrors(['service' => 'Deal memerlukan setidaknya satu layanan sebelum Closing.']);
        }
        if (! $deal->target_date) {
            return back()->withErrors(['target_date' => 'Target closing harus diisi sebelum Closing.']);
        }

        try {
            DB::transaction(function () use ($deal): void {
                $deal->update([
                    'stage' => Deal::CLOSED_STAGE,
                    'status' => 'won',
                    'won_at' => now(),
                    'closed_at' => now(),
                    'last_update' => now(),
                ]);
                event(new DealClosed($deal->fresh()));
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['confirm' => 'Closing gagal: '.$e->getMessage()]);
        }

        WorkloadService::syncFor($deal->pic_employee_id);

        return redirect()->route('deals.show', $deal)->with('status', 'Deal ditutup. Project dan Invoice Request otomatis dibuat.');
    }

    public function markLost(Request $request, Deal $deal): RedirectResponse
    {
        $data = $request->validate(['reason_lost' => ['required', 'string', 'max:500']]);
        $deal->update([
            'status' => 'lost',
            'reason_lost' => $data['reason_lost'],
            'closed_at' => now(),
            'last_update' => now(),
        ]);
        AutomationLogger::log('deal_lost', 'deal', $deal->deal_id, 'success');

        return redirect()->route('deals.show', $deal)->with('status', 'Deal ditandai Lost.');
    }

    private function validated(Request $request, ?Deal $deal = null): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,client_id'],
            'pic_employee_id' => ['required', 'exists:employees,employee_id'],
            'stage' => ['required', 'string'],
            'service' => ['required', 'array', 'min:1'],
            'service.*' => ['string'],
            'deal_value' => ['required', 'numeric', 'min:0'],
            'target_date' => ['required', 'date'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'next_action' => ['nullable', 'string', 'max:1000'],
            'next_action_date' => ['nullable', 'date'],
            'status' => ['required', 'in:open,won,lost,on_hold'],
            'reason_lost' => ['nullable', 'string', 'max:500'],
        ]);
    }
}