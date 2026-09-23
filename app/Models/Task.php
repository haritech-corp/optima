<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasHumanId;

    protected $primaryKey = 'task_id';
    protected $idPrefix = 'TSK';
    protected $guarded = [];
    protected $casts = [
        'start_date' => 'date',
        'deadline' => 'date',
        'progress_percent' => 'integer',
        'revision_count' => 'integer',
        'is_completed' => 'boolean',
        'template_sequence' => 'integer',
        'completed_at' => 'datetime',
    ];

    public const DONE = 'Done';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'activity_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pic_employee_id', 'employee_id');
    }

    public function evidence(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Evidence::class, 'task_id', 'task_id');
    }

    public function approvalRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ApprovalRequest::class, 'record_id', 'task_id')->where('record_type', 'task')->latest();
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', today())->where('is_completed', false);
    }

    protected static function booted(): void
    {
        static::saving(function (Task $task): void {
            if ($task->progress_percent >= 100) {
                $task->is_completed = true;
                $task->completed_at ??= now();
            }
        });
    }
}