<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\InvoiceRequest;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ServiceTemplate;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Wiring of the cross-module "lead to cash" workflow: closing a deal creates a
 * Project Master plus its service-template tasks and an Invoice Request.
 */
class ProjectComposer
{
    /**
     * Create a project from a closed deal, generate tasks from the selected
     * service templates and open an invoice request (blueprint automation).
     */
    public static function createFromDeal(Deal $deal, string $mainPicEmployeeId): Project
    {
        return DB::transaction(function () use ($deal, $mainPicEmployeeId): Project {
            $project = Project::create([
                'project_name' => trim(($deal->client->name ?? 'Client').' — '.implode(' + ', array_map(fn ($s) => (string) $s, $deal->service ?? []))),
                'client_id' => $deal->client_id,
                'deal_id' => $deal->deal_id,
                'service' => $deal->service ?? [],
                'start_date' => today(),
                'deadline' => $deal->target_date,
                'overall_status' => 'Planning',
                'progress_percent' => 0,
                'main_pic_employee_id' => $mainPicEmployeeId,
                'created_by' => AccessService::employeeId(),
            ]);

            ProjectBudget::create([
                'project_id' => $project->project_id,
                'initial_budget' => $deal->deal_value,
                'actual' => 0,
                'remaining' => $deal->deal_value,
            ]);

            self::generateTasksFromTemplates($project, $mainPicEmployeeId);

            $invoiceRequest = InvoiceRequest::create([
                'requester_employee_id' => $mainPicEmployeeId,
                'project_id' => $project->project_id,
                'client_id' => $deal->client_id,
                'nominal' => $deal->deal_value,
                'description' => 'Otomatis dibuat dari Closing '.$deal->deal_id,
                'date' => today(),
                'approval_status' => 'Diajukan',
                'created_by' => AccessService::employeeId(),
            ]);

            // Route the invoice through the shared approval engine (manager chain).
            $requester = \App\Models\Employee::query()->whereKey($mainPicEmployeeId)->first();
            if ($requester?->manager_employee_id) {
                $approval = ApprovalEngine::submit(
                    'invoice_request',
                    $invoiceRequest->invoice_request_id,
                    $mainPicEmployeeId,
                    [['approver_type' => 'manager', 'approver_employee_id' => $requester->manager_employee_id]],
                );
                $invoiceRequest->update(['approval_request_id' => $approval->request_id]);
            }

            $deal->update(['project_id' => $project->project_id]);

            return $project;
        });
    }

    /**
     * Generate Activity + Task records from the default templates of every
     * service line selected on the project (template behavior).
     */
    public static function generateTasksFromTemplates(Project $project, string $defaultPic): void
    {
        $services = is_array($project->service) ? $project->service : (array) $project->service;

        foreach ($services as $serviceId) {
            $activity = Activity::create([
                'project_id' => $project->project_id,
                'service_id' => $serviceId,
                'activity_name' => self::serviceName($serviceId).' — '.$project->project_name,
                'owner_employee_id' => $defaultPic,
                'start_date' => $project->start_date,
                'deadline' => $project->deadline,
                'status' => 'Planning',
                'progress_percent' => 0,
            ]);

            $templates = ServiceTemplate::query()
                ->where('service_id', $serviceId)
                ->where('is_default', true)
                ->orderBy('sequence')
                ->get();

            $templates->each(function (ServiceTemplate $template) use ($project, $activity, $defaultPic, $serviceId): void {
                Task::create([
                    'project_id' => $project->project_id,
                    'activity_id' => $activity->activity_id,
                    'task_name' => $template->step_name,
                    'service_id' => $serviceId,
                    'stage' => 'Stage '.$template->sequence,
                    'pic_employee_id' => $defaultPic,
                    'start_date' => $project->start_date,
                    'deadline' => $project->deadline,
                    'status' => 'To Do',
                    'approval_status' => 'Pending',
                    'source' => 'template',
                    'template_sequence' => $template->sequence,
                    'created_by' => AccessService::employeeId(),
                ]);
            });
        }
    }

    private static function serviceName(string $serviceId): string
    {
        static $names = [
            'PR' => 'Public Relation',
            'KOL' => 'KOL & KOC Marketing',
            'OOH' => 'OOH Advertising',
            'PMS' => 'Programmatic & Social Media Ads',
            'WAD' => 'Website & Apps Development',
            'CRP' => 'Creative Production',
        ];

        return $names[$serviceId] ?? $serviceId;
    }
}