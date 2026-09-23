<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasHumanId;

    protected $primaryKey = 'activity_id';
    protected $idPrefix = 'ACT';
    protected $guarded = [];
    protected $casts = [
        'start_date' => 'date',
        'deadline' => 'date',
        'progress_percent' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id', 'employee_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'activity_id', 'activity_id');
    }
}