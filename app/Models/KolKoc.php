<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KolKoc extends Model
{
    use HasHumanId;

    protected $primaryKey = 'kol_id';
    protected $idPrefix = 'KOL';
    protected $guarded = [];
    protected $casts = ['rate_card' => 'decimal:2', 'archived_at' => 'datetime'];

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'entity_id', 'kol_id')->where('entity_type', 'kol_koc');
    }
}