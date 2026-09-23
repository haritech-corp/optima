<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deal extends Model
{
    use HasHumanId;

    protected $primaryKey = 'deal_id';
    protected $idPrefix = 'DEAL';
    protected $guarded = [];
    protected $casts = [
        'service' => 'array',
        'deal_value' => 'decimal:2',
        'target_date' => 'date',
        'probability' => 'integer',
        'next_action_date' => 'date',
        'last_update' => 'datetime',
        'won_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const CLOSED_STAGE = 'Closing';

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pic_employee_id', 'employee_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'entity_id', 'deal_id')->where('entity_type', 'deal');
    }

    public function scopeWonOrClosing($query)
    {
        return $query->where('status', 'won')->orWhere('stage', self::CLOSED_STAGE);
    }
}