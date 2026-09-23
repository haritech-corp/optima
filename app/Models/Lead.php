<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasUuids;
    protected $guarded = [];
    protected $casts = ['next_follow_up_at' => 'datetime', 'archived_at' => 'datetime'];
    public function owner(): BelongsTo { return $this->belongsTo(UserProfile::class, 'owner_id'); }
    public function interactions(): HasMany { return $this->hasMany(Interaction::class)->latest('occurred_at'); }
    public function followUps(): HasMany { return $this->hasMany(FollowUp::class)->latest('due_at'); }
}
