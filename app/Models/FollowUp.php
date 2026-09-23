<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasHumanId;

    protected $primaryKey = 'follow_up_id';
    protected $idPrefix = 'FUP';
    protected $guarded = [];
    protected $casts = ['due_at' => 'datetime', 'completed_at' => 'datetime'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_employee_id', 'employee_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function entity(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }
}