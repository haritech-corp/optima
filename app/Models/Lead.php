<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasHumanId;

    protected $primaryKey = 'lead_id';
    protected $idPrefix = 'LED';
    protected $guarded = [];
    protected $casts = [
        'score' => 'integer',
        'converted_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pic_employee_id', 'employee_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'entity_id', 'lead_id')->where('entity_type', 'lead')->latest('interaction_date');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class, 'entity_id', 'lead_id')->where('entity_type', 'lead');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }
}