<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\FollowUp;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Services\AccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $me = AccessService::employeeId();
        $departmentId = AccessService::departmentId();
        $isGlobal = AccessService::isSuperAdminGlobal();
        $manageDept = in_array(AccessService::role(), [AccessService::SUPER_ADMIN_DEPARTMENT, AccessService::KOORDINATOR], true);

        // Scope helper: global → all; department managers → own dept; staff → own records.
        $projectQuery = Project::query()->whereNull('archived_at');
        if (! $isGlobal) {
            $projectQuery->whereHas('mainPic', fn ($q) => $q->where('department_id', $departmentId));
        }

        $dealQuery = Deal::query()->where('status', 'open');
        if (! $isGlobal) {
            $dealQuery->whereHas('pic', fn ($q) => $q->where('department_id', $departmentId));
        }

        $activeProjects = (clone $projectQuery)->whereNotIn('overall_status', ['Completed', 'Closed', 'Cancelled']);
        $overdueTasks = Task::query()->where('is_completed', false)->where('deadline', '<', today())
            ->when(! $isGlobal, fn ($q) => $q->whereHas('project.mainPic', fn ($x) => $x->where('department_id', $departmentId)));

        $myTasks = Task::query()->where('pic_employee_id', $me)->where('is_completed', false)
            ->orderByRaw('deadline is null, deadline asc')->limit(8)->get();

        $invoiceQuery = Invoice::query()->when(! AccessService::can('finance.value.view'), fn ($q) => $q->select('invoice_id', 'client_id', 'status'));
        $financeReceivable = AccessService::can('finance.value.view')
            ? (float) Invoice::query()->whereNotIn('status', ['Lunas'])->sum('nominal')
            : null;

        return view('dashboard', [
            'view' => $request->string('view')->toString() ?: 'management',
            'activeProjects' => (clone $activeProjects)->count(),
            'pipelineValue' => AccessService::can('finance.value.view') ? (clone $dealQuery)->sum('deal_value') : null,
            'openDeals' => (clone $dealQuery)->count(),
            'closingCount' => (clone $dealQuery)->where('stage', 'Closing')->count(),
            'overdueTaskCount' => (clone $overdueTasks)->count(),
            'blockedTaskCount' => (clone $overdueTasks)->where('status', 'Blocked')->count(),
            'atRiskProjects' => (clone $activeProjects)->where('overall_status', 'At Risk'),
            'serviceMix' => (clone $activeProjects)->get()->flatMap(fn ($p) => $p->service ?? [])->countBy()->sortDesc()->take(6),
            'invoiceStatus' => Invoice::query()
                ->when(! AccessService::can('finance.value.view'), fn ($q) => $q->select('status'))
                ->get()
                ->groupBy('status')
                ->map->count(),
            'financeReceivable' => $financeReceivable,
            'myTasks' => $myTasks,
            'myFollowUps' => FollowUp::query()->where('assignee_employee_id', $me)->where('status', 'open')->orderBy('due_at')->limit(8)->get(),
            'myApprovals' => ApprovalRequest::query()
                ->with('requester', 'steps')
                ->where('status', 'Pending')
                ->where('current_approver_employee_id', $me)
                ->limit(8)->get(),
            'recentNotifications' => Notification::query()->where('employee_id', $me)->latest()->limit(8)->get(),
            'teamWorkload' => $manageDept
                ? \App\Models\EmployeeWorkload::query()->with('employee')
                    ->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId))
                    ->orderByDesc('total_load')->limit(8)->get()
                : collect(),
            'clients' => Client::query()->whereNull('archived_at')->count(),
            'employees' => Employee::query()->where('status', 'active')->count(),
        ]);
    }
}