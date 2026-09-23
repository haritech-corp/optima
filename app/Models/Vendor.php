<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasHumanId;

    protected $primaryKey = 'vendor_id';
    protected $idPrefix = 'VND';
    protected $guarded = [];
    protected $casts = ['archived_at' => 'datetime'];

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'entity_id', 'vendor_id')->where('entity_type', 'vendor');
    }
}