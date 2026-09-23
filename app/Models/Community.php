<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    use HasHumanId;

    protected $primaryKey = 'community_id';
    protected $idPrefix = 'COM';
    protected $guarded = [];
    protected $casts = ['archived_at' => 'datetime'];

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pic_employee_id', 'employee_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'entity_id', 'community_id')->where('entity_type', 'community');
    }
}