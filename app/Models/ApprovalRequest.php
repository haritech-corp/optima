<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    use HasHumanId;

    protected $primaryKey = 'request_id';
    protected $idPrefix = 'APV';
    protected $guarded = [];
    protected $casts = ['submitted_at' => 'datetime', 'decided_at' => 'datetime'];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_employee_id', 'employee_id');
    }

    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'current_approver_employee_id', 'employee_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'approval_request_id', 'request_id')->orderBy('sequence');
    }

    public static function recordTypes(): array
    {
        return [
            'invoice_request' => 'Invoice Request',
            'payment' => 'Payment',
            'reimbursement' => 'Reimbursement',
            'leave_request' => 'Cuti / Izin',
            'task' => 'Content Approval',
            'budget' => 'Budget Approval',
            'client_approval' => 'Client Approval',
        ];
    }
}