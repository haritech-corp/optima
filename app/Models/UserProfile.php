<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasUuids;
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
}
