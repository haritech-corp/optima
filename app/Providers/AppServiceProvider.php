<?php

namespace App\Providers;

use App\Events\DealClosed;
use App\Listeners\HandleDealClosing;
use App\Models\Activity;
use App\Models\ApprovalRequest;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceRequest;
use App\Models\LeaveRequest;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Reimbursement;
use App\Models\Task;
use App\Services\AccessService;
use App\Services\AuditLogger;
use App\Services\WorkloadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Models that must write an audit trail on every important change.
     *
     * @var list<class-string<Model>>
     */
    protected array $auditedModels = [
        Lead::class,
        Client::class,
        Deal::class,
        Project::class,
        Activity::class,
        Task::class,
        Asset::class,
        Invoice::class,
        InvoiceRequest::class,
        Payment::class,
        Reimbursement::class,
        LeaveRequest::class,
        Employee::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(DealClosed::class, HandleDealClosing::class);

        $this->registerAuditObserver();
        $this->registerViewComposers();
    }

    /** Field-level audit trail for audited models (blueprint audit rule). */
    private function registerAuditObserver(): void
    {
        foreach ($this->auditedModels as $model) {
            $model::updating(function (Model $record): void {
                AuditLogger::changed($record, $record->getOriginal(), AccessService::employeeId());
            });

            $model::creating(function (Model $record): void {
                AuditLogger::log(class_basename($record), (string) $record->getKey(), 'created', null, 'record_created', AccessService::employeeId());
            });
        }

        // Workload snapshot stays in sync with task changes.
        Task::saved(fn () => WorkloadService::syncFor());
    }

    private function registerViewComposers(): void
    {
        View::composer('*', function ($view): void {
            $user = AccessService::user();
            $view->with([
                'currentUser' => $user,
                'currentEmployee' => $user ? Employee::query()->with('department')->whereKey(data_get($user, 'employee_id'))->first() : null,
                'visibleModules' => $this->modulesForUser(),
                'unreadNotifications' => $user
                    ? \App\Models\Notification::unreadCount(data_get($user, 'employee_id'))
                    : 0,
                'pendingApprovalCount' => AccessService::can('approval.view')
                    ? ApprovalRequest::query()->where('status', 'Pending')
                        ->where('current_approver_employee_id', data_get($user, 'employee_id'))
                        ->count()
                    : 0,
            ]);
        });
    }

    /** Sidebar navigation visibility follows role + department module access. */
    private function modulesForUser(): array
    {
        $role = AccessService::role();
        $departmentId = AccessService::departmentId();
        $all = config('optima.modules');

        if (AccessService::isSuperAdminGlobal()) {
            return $all;
        }

        $map = AccessService::permissionMap($role);
        $departmentModules = \App\Models\DepartmentModuleAccess::query()
            ->where('department_id', $departmentId)
            ->pluck('access_level', 'module')
            ->all();

        $visible = [];
        foreach ($all as $key => $label) {
            if (array_key_exists("{$key}.view", $map) || isset($departmentModules[$key])) {
                $visible[$key] = $label;
            }
        }

        return $visible ?: ['dashboard' => 'Dashboard'];
    }
}