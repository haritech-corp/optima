<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasHumanId;

    protected $primaryKey = 'client_id';
    protected $idPrefix = 'CLI';
    protected $guarded = [];
    protected $casts = [
        'service_history' => 'array',
        'total_contract' => 'decimal:2',
        'archived_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id', 'employee_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'client_id', 'client_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'client_id', 'client_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id', 'client_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'entity_id', 'client_id')->where('entity_type', 'client');
    }
}