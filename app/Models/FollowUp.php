<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasUuids;
    protected $guarded = [];
    protected $casts = ['due_at' => 'datetime', 'completed_at' => 'datetime'];
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(UserProfile::class, 'assignee_id'); }
}
