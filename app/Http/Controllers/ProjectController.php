<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\CampaignProgressLog;
use App\Models\Client;
use App\Models\ContentProductionLog;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\StatusMaster;
use App\Models\Task;
use App\Services\AccessService;
use App\Services\AutomationLogger;
use App\Services\ProjectComposer;
use App\Services\WorkloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:project.view')->only(['index', 'show']);
        $this->middleware('permission:project.create')->only(['create', 'store']);
        $this->middleware('permission:project.edit')->only(['update', 'archive', 'storeActivity', 'storeTask', 'storeCampaignLog', 'storeContentLog']);
    }

    public function index(Request $request): View
    {
        $query = Project::query()->with(['client', 'mainPic'])->whereNull('archived_at');

        if (AccessService::moduleScope('project') === 'own') {
            $query->where('main_pic_employee_id', AccessService::employeeId());
        }

        foreach (['overall_status', 'service'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->string($filter)->toString());
            }
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->string('client_id')->toString());
        }
        if ($request->filled('pic_employee_id')) {
            $query->where('main_pic_employee_id', $request->string('pic_employee_id')->toString());
        }
        if ($request->filled('period_from')) {
            $query->whereDate('start_date', '>=', $request->string('period_from')->toString());
        }
        if ($request->filled('period_to')) {
            $query->whereDate('deadline', '<=', $request->string('period_to')->toString());
        }

        return view('projects.index', [
            'projects' => $query->latest()->paginate(15)->withQueryString(),
            'statusOptions' => StatusMaster::labels('project_status'),
            'statusCounts' => Project::query()->whereNull('archived_at')
                ->selectRaw('overall_status, count(*) as total')->groupBy('overall_status')->pluck('total', 'overall_status'),
            'runningCount' => Project::query()->whereNull('archived_at')->where('overall_status', 'Active')->count(),
            'atRiskCount' => Project::query()->whereNull('archived_at')->where('overall_status', 'At Risk')->count(),
        ]);
    }

    public function create(): View
    {
        return view('projects.form', [
            'project' => new Project,
            'clients' => Client::query()->whereNull('archived_at')->orderBy('name')->get(),
            'employees' => \App\Models\Employee::query()->where('status', 'active')->orderBy('name')->get(),
            'services' => config('optima.service_lines'),
            'statusOptions' => StatusMaster::labels('project_status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:200'],
            'client_id' => ['required', 'exists:clients,client_id'],
            'service' => ['required', 'array', 'min:1'],
            'service.*' => ['string'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
            'overall_status' => ['required', 'string'],
            'main_pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'description' => ['nullable', 'string'],
        ]);

        $data['main_pic_employee_id'] ??= AccessService::employeeId();
        $data['created_by'] = AccessService::employeeId();

        $project = Project::create($data);

        ProjectBudget::create([
            'project_id' => $project->project_id,
            'initial_budget' => 0,
            'actual' => 0,
            'remaining' => 0,
        ]);

        if (! empty($data['service'])) {
            ProjectComposer::generateTasksFromTemplates($project, $data['main_pic_employee_id']);
            AutomationLogger::log('project_template_tasks', 'project', $project->project_id, 'success');
        }

        WorkloadService::syncFor($data['main_pic_employee_id']);

        return redirect()->route('projects.show', $project)->with('status', 'Project berhasil dibuat. Task dari template layanan telah dibuat.');
    }

    public function show(Project $project): View
    {
        $project->load([
            'client', 'deal', 'mainPic', 'activities.service', 'activities.tasks.pic',
            'tasks.pic', 'tasks.evidence', 'campaignLogs', 'contentLogs', 'budget',
        ]);

        return view('projects.show', [
            'project' => $project,
            'statusOptions' => StatusMaster::labels('project_status'),
            'taskStatusOptions' => StatusMaster::labels('task_status'),
            'employees' => \App\Models\Employee::query()->where('status', 'active')->orderBy('name')->get(),
            'services' => config('optima.service_lines'),
            'serviceEntities' => \App\Models\Service::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:200'],
            'overall_status' => ['required', 'string'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'main_pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        if ((string) ($data['main_pic_employee_id'] ?? '') !== $project->main_pic_employee_id && ! empty($data['main_pic_employee_id'])) {
            WorkloadService::syncFor($project->main_pic_employee_id);
            WorkloadService::syncFor($data['main_pic_employee_id']);
        }

        $project->update(array_merge($data, ['progress_percent' => $data['progress_percent'] ?: $project->progress_percent]));

        return back()->with('status', 'Project berhasil diperbarui.');
    }

    public function archive(Project $project): RedirectResponse
    {
        $project->update(['archived_at' => now()]);

        return redirect()->route('projects.index')->with('status', 'Project diarsipkan.');
    }

    public function storeActivity(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,service_id'],
            'activity_name' => ['required', 'string', 'max:200'],
            'owner_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
            'status' => ['required', 'string'],
        ]);

        Activity::create($data + ['project_id' => $project->project_id]);

        return back()->with('status', 'Activity berhasil ditambahkan.');
    }

    public function storeTask(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'activity_id' => ['nullable', 'exists:activities,activity_id'],
            'task_name' => ['required', 'string', 'max:200'],
            'brief' => ['nullable', 'string'],
            'service_id' => ['nullable', 'exists:services,service_id'],
            'stage' => ['nullable', 'string', 'max:80'],
            'pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
            'status' => ['required', 'string'],
        ]);

        Task::create($data + [
            'project_id' => $project->project_id,
            'source' => 'manual',
            'approval_status' => 'Pending',
            'created_by' => AccessService::employeeId(),
        ]);
        WorkloadService::syncFor();

        return back()->with('status', 'Task manual berhasil ditambahkan.');
    }

    public function storeCampaignLog(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'metric' => ['required', 'string', 'max:100'],
            'value' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        CampaignProgressLog::create($data + ['project_id' => $project->project_id, 'created_by' => AccessService::employeeId()]);

        return back()->with('status', 'Progress campaign dicatat.');
    }

    public function storeContentLog(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'content_type' => ['required', 'string', 'max:100'],
            'production_status' => ['required', 'string'],
            'file_or_preview' => ['nullable', 'string'],
            'client_approval' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        ContentProductionLog::create($data + ['project_id' => $project->project_id, 'created_by' => AccessService::employeeId()]);

        return back()->with('status', 'Log produksi konten dicatat.');
    }

    public function destroyBudget(Project $project): RedirectResponse
    {
        return back();
    }

    public function budgets(): View
    {
        return view('finance.budgets', [
            'budgets' => ProjectBudget::query()->with('project.client')->orderByDesc('remaining')->paginate(15),
        ]);
    }
}