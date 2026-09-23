<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasHumanId;

    protected $primaryKey = 'project_id';
    protected $idPrefix = 'PRJ';
    protected $guarded = [];
    protected $casts = [
        'service' => 'array',
        'start_date' => 'date',
        'deadline' => 'date',
        'progress_percent' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'deal_id', 'deal_id');
    }

    public function mainPic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'main_pic_employee_id', 'employee_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'project_id', 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id', 'project_id');
    }

    public function budget(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProjectBudget::class, 'project_id', 'project_id');
    }

    public function campaignLogs(): HasMany
    {
        return $this->hasMany(CampaignProgressLog::class, 'project_id', 'project_id');
    }

    public function contentLogs(): HasMany
    {
        return $this->hasMany(ContentProductionLog::class, 'project_id', 'project_id');
    }

    /** Consistent progress = unweighted mean of task progress. */
    public function recalculateProgress(): void
    {
        $tasks = Task::query()->where('project_id', $this->project_id)->whereNull('archived_at');
        $avg = (float) $tasks->avg('progress_percent');
        $this->forceFill(['progress_percent' => (int) round($avg)])->saveQuietly();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at')
            ->whereNotIn('overall_status', ['Completed', 'Closed', 'Cancelled']);
    }
}