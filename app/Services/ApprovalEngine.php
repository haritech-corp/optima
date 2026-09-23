<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\Employee;
use App\Models\InvoiceRequest;
use App\Models\LeaveRequest;
use App\Models\Reimbursement;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reusable approval engine, shared across every module that needs approval
 * (leave, invoice request, payment, reimbursement, content/client approval…).
 *
 * A request is created with an ordered list of steps; the engine advances the
 * current approver after every decision and stores the full (undeletable)
 * history in approval_steps.
 */
class ApprovalEngine
{
    public const STATUS_PENDING = 'Pending';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_REVISION = 'Revision';

    /**
     * Submit a new approval request.
     *
     * @param  array<int, array{approver_type: string, approver_employee_id?: string|null}>  $steps
     */
    public static function submit(string $recordType, string $recordId, string $requesterEmployeeId, array $steps): ApprovalRequest
    {
        return DB::transaction(function () use ($recordType, $recordId, $requesterEmployeeId, $steps): ApprovalRequest {
            $current = $steps[0]['approver_employee_id'] ?? null;

            $request = ApprovalRequest::create([
                'record_type' => $recordType,
                'record_id' => $recordId,
                'requester_employee_id' => $requesterEmployeeId,
                'current_approver_employee_id' => $current,
                'status' => self::STATUS_PENDING,
                'created_by' => AccessService::employeeId(),
            ]);

            foreach (array_values($steps) as $i => $step) {
                ApprovalStep::create([
                    'approval_request_id' => $request->request_id,
                    'sequence' => $i + 1,
                    'approver_type' => $step['approver_type'] ?? 'manager',
                    'approver_employee_id' => $step['approver_employee_id'] ?? null,
                ]);
            }

            if ($current) {
                NotificationService::send($current, 'Persetujuan diperlukan', "Ada approval baru untuk {$recordType} {$recordId}", route('approvals.index'));
            }

            return $request;
        });
    }

    /** Record a decision from the current approver and advance the workflow. */
    public static function decide(ApprovalRequest $request, string $deciderEmployeeId, string $decision, ?string $note = null): ApprovalRequest
    {
        if (! in_array($decision, ['Approve', 'Reject', 'Revision'], true)) {
            throw new RuntimeException('Keputusan tidak valid.');
        }
        if ($request->status !== self::STATUS_PENDING) {
            throw new RuntimeException('Approval ini sudah diputuskan.');
        }
        if ((string) $request->current_approver_employee_id !== $deciderEmployeeId) {
            throw new RuntimeException('Anda bukan approver saat ini untuk request ini.');
        }

        $currentStep = $request->steps()
            ->whereNull('decision')
            ->orderBy('sequence')
            ->firstOrFail();

        $currentStep->update([
            'decision' => $decision,
            'timestamp' => now(),
            'note' => $note,
            'approver_employee_id' => $deciderEmployeeId,
        ]);

        if ($decision !== 'Approve') {
            $request->update(['status' => $decision === 'Revision' ? self::STATUS_REVISION : self::STATUS_REJECTED, 'decided_at' => now()]);
            self::applyStatusToRecord($request->record_type, $request->record_id, $request->status, $deciderEmployeeId);
            NotificationService::send($request->requester_employee_id, 'Hasil approval', "{$request->record_type} {$request->record_id}: {$decision}");

            return $request;
        }

        $nextStep = $request->steps()
            ->whereNull('decision')
            ->orderBy('sequence')
            ->first();

        if ($nextStep) {
            $request->update(['current_approver_employee_id' => $nextStep->approver_employee_id]);
            if ($nextStep->approver_employee_id) {
                NotificationService::send($nextStep->approver_employee_id, 'Persetujuan diperlukan', "Lanjutan approval {$request->record_type} {$request->record_id}", route('approvals.index'));
            }
        } else {
            $request->update(['status' => self::STATUS_APPROVED, 'current_approver_employee_id' => null, 'decided_at' => now()]);
            self::applyStatusToRecord($request->record_type, $request->record_id, self::STATUS_APPROVED, $deciderEmployeeId);
            NotificationService::send($request->requester_employee_id, 'Approval disetujui', "{$request->record_type} {$request->record_id} telah disetujui.");
        }

        return $request;
    }

    /**
     * Map an approval verdict back onto the source record's own status field
     * (invoice request, reimbursement, leave request, task/content approval).
     */
    private static function applyStatusToRecord(string $recordType, string $recordId, string $verdict, string $deciderId): void
    {
        $approved = $verdict === self::STATUS_APPROVED;
        $rejected = $verdict === self::STATUS_REJECTED;

        match ($recordType) {
            'invoice_request' => InvoiceRequest::whereKey($recordId)->update([
                'approval_status' => $approved ? 'Disetujui' : ($rejected ? 'Ditolak' : 'Diajukan'),
            ]),
            'reimbursement' => Reimbursement::whereKey($recordId)->update([
                'approval_status' => $approved ? 'Disetujui Atasan' : ($rejected ? 'Ditolak' : 'Diajukan'),
            ]),
            'leave_request' => LeaveRequest::whereKey($recordId)->update([
                'approval_status' => $approved ? 'Disetujui Atasan' : ($rejected ? 'Ditolak' : 'Diajukan'),
            ]),
            'task' => Task::whereKey($recordId)->update([
                'approval_status' => $approved ? 'Approved' : ($rejected ? 'Rejected' : 'Revision'),
                'revision_count' => $verdict === self::STATUS_REVISION
                    ? DB::raw('coalesce(revision_count,0) + 1')
                    : DB::raw('revision_count'),
            ]),
            default => null,
        };

        if (! $approved && ! $rejected && $recordType !== 'task') {
            // Revision leaves the record in its draft/pending state.
            return;
        }
    }

    /** Resolve the default approval chain (manager → department head) of an employee. */
    public static function chainForEmployee(Employee $employee): array
    {
        $steps = [];
        if ($employee->manager_employee_id) {
            $steps[] = ['approver_type' => 'manager', 'approver_employee_id' => $employee->manager_employee_id];
        }

        return $steps;
    }
}