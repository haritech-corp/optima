<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaOutlet extends Model
{
    use HasHumanId;

    protected $primaryKey = 'media_id';
    protected $idPrefix = 'MED';
    protected $guarded = [];
    protected $casts = ['archived_at' => 'datetime'];

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'entity_id', 'media_id')->where('entity_type', 'media');
    }
}