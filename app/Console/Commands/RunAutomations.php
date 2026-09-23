<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\Task;
use App\Services\AutomationLogger;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Scheduler-friendly automation runner. Executes the blueprint's trigger/action
 * pairs that depend on time (H-3/H-1 reminders, overdue escalation, approval
 * reminders, at-risk alerts, budget thresholds, invoice maturity).
 */
class RunAutomations extends Command
{
    protected $signature = 'optima:automations {--dry-run}';

    protected $description = 'Run scheduled OPTIMA automations (reminders, escalations, alerts)';

    public function handle(): int
    {
        $jobs = [
            'task_deadline_reminder' => fn () => $this->taskDeadlineReminders(),
            'task_overdue_escalation' => fn () => $this->escalateOverdueTasks(),
            'approval_reminder' => fn () => $this->remindPendingApprovals(),
            'project_at_risk' => fn () => $this->alertAtRiskProjects(),
            'budget_threshold' => fn () => $this->alertBudgetThresholds(),
            'invoice_maturity' => fn () => $this->alertInvoiceMaturity(),
        ];

        foreach ($jobs as $name => $job) {
            try {
                $job();
                if ($this->option('dry-run')) {
                    $this->info("[dry-run] {$name}: OK");
                }
            } catch (\Throwable $e) {
                $this->error("{$name}: ".$e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /** H-3 / H-1 reminders to the PIC of every open task. */
    private function taskDeadlineReminders(): void
    {
        $hours = config('optima.automation.task_reminder_hours', [72, 24]);
        foreach ($hours as $h) {
            $window = now()->addHours($h);
            $tasks = Task::query()
                ->where('is_completed', false)
                ->whereNotNull('pic_employee_id')
                ->whereDate('deadline', $window->toDateString())
                ->get();

            foreach ($tasks as $task) {
                $already = \App\Models\Notification::query()
                    ->where('employee_id', $task->pic_employee_id)
                    ->where('title', 'like', '%Deadline H-'.($h / 24).'%')
                    ->whereDate('created_at', today())
                    ->exists();

                if ($already) {
                    continue;
                }

                NotificationService::send(
                    $task->pic_employee_id,
                    'Deadline H-'.($h / 24).' — '.$task->task_name,
                    "Task {$task->task_id} jatuh tempo {$window->format('d M Y')}.",
                    route('projects.show', $task->project_id),
                    'warning',
                );
                AutomationLogger::log('task_deadline_reminder', 'task', $task->task_id);
            }
        }
    }

    /** Overdue tasks notify PIC + coordinator; escalate after threshold. */
    private function escalateOverdueTasks(): void
    {
        $thresholdDays = (int) config('optima.automation.overdue_escalation_hours', 48) / 24;

        Task::query()
            ->where('is_completed', false)
            ->whereNotNull('pic_employee_id')
            ->whereDate('deadline', '<', today())
            ->get()
            ->each(function (Task $task) use ($thresholdDays): void {
                $overdueDays = today()->diffInDays($task->deadline);
                $map = ['task' => $task->task_id];

                NotificationService::send(
                    $task->pic_employee_id,
                    'Task overdue',
                    "Task {$task->task_name} ({$task->task_id}) telah melewati deadline.",
                    route('projects.show', $task->project_id),
                    'danger',
                );
                AutomationLogger::log('task_overdue', 'task', $task->task_id);

                if ($overdueDays >= $thresholdDays) {
                    $coordinator = Employee::query()
                        ->where('department_id', $task->pic?->department_id)
                        ->where('access_role', 'koordinator')
                        ->first();

                    NotificationService::send(
                        $coordinator?->employee_id,
                        'Eskalasi task overdue',
                        "Task {$task->task_name} overdue > {$thresholdDays} hari pada proyek {$task->project_id}.",
                        route('projects.show', $task->project_id),
                        'danger',
                    );
                    AutomationLogger::log('task_overdue_escalation', 'task', $task->task_id);
                }
            });
    }

    /** Periodic reminder for approval requests stuck in Pending. */
    private function remindPendingApprovals(): void
    {
        $days = (int) config('optima.automation.approval_reminder_days', 2);

        ApprovalRequest::query()
            ->where('status', 'Pending')
            ->where('updated_at', '<=', now()->subDays($days))
            ->get()
            ->each(function (ApprovalRequest $request): void {
                NotificationService::send(
                    $request->current_approver_employee_id,
                    'Reminder approval',
                    "Approval {$request->record_type} {$request->record_id} masih menunggu keputusan Anda.",
                    route('approvals.index'),
                    'warning',
                );
                AutomationLogger::log('approval_reminder', $request->record_type, $request->record_id);
            });
    }

    /** Project = At Risk notifies the project lead and coordinator. */
    private function alertAtRiskProjects(): void
    {
        Project::query()
            ->where('overall_status', 'At Risk')
            ->whereNull('archived_at')
            ->get()
            ->each(function (Project $project): void {
                $leadId = $project->main_pic_employee_id;
                if ($leadId) {
                    NotificationService::send($leadId, 'Proyek At Risk', "Project {$project->project_name} berstatus At Risk.", route('projects.show', $project->project_id), 'danger');
                    AutomationLogger::log('project_at_risk', 'project', $project->project_id);
                }
            });
    }

    /** Budget threshold reached → alert finance. */
    private function alertBudgetThresholds(): void
    {
        $threshold = (int) config('optima.automation.budget_threshold_percent', 90);

        ProjectBudget::query()
            ->get()
            ->filter(fn (ProjectBudget $budget) => $budget->initial_budget > 0
                && ($budget->actual / $budget->initial_budget) * 100 >= $threshold)
            ->each(function (ProjectBudget $budget): void {
                foreach (Employee::financeTeam() as $finance) {
                    NotificationService::send($finance->employee_id, 'Peringatan budget', "Budget project {$budget->project_id} telah mencapai {$threshold}% dari anggaran awal.", route('finance.invoices'), 'danger');
                }
                AutomationLogger::log('budget_threshold', 'project_budget', $budget->budget_id);
            });
    }

    /** Invoice due soon or overdue → notify finance + deal owner. */
    private function alertInvoiceMaturity(): void
    {
        $days = (int) config('optima.automation.invoice_due_reminder_days', 7);

        Invoice::query()
            ->whereIn('status', ['Menunggu Pelunasan', 'DP Diterima'])
            ->where(function ($q) use ($days): void {
                $q->whereDate('due_date', '<=', today()->addDays($days));
            })
            ->get()
            ->each(function (Invoice $invoice): void {
                $financeTeam = Employee::financeTeam();
                foreach ($financeTeam as $finance) {
                    NotificationService::send($finance->employee_id, 'Invoice jatuh tempo', "Invoice {$invoice->invoice_id} jatuh tempo {$invoice->due_date?->format('d M Y')}.", route('finance.invoices'), 'warning');
                }
                AutomationLogger::log('invoice_maturity', 'invoice', $invoice->invoice_id);
            });
    }
}