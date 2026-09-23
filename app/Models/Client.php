<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasUuids;
    protected $guarded = [];
    protected $casts = ['archived_at' => 'datetime'];
    public function owner(): BelongsTo { return $this->belongsTo(UserProfile::class, 'owner_id'); }
    public function sourceLead(): BelongsTo { return $this->belongsTo(Lead::class, 'converted_from_lead_id'); }
}
