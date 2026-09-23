<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasHumanId;

    protected $primaryKey = 'leave_id';
    protected $idPrefix = 'LEV';
    protected $guarded = [];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_days' => 'decimal:1',
        'remaining_quota' => 'decimal:1',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function approvalRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ApprovalRequest::class, 'record_id', 'leave_id')->where('record_type', 'leave_request')->latest();
    }
}