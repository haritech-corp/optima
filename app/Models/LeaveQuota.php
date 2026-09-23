<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveQuota extends Model
{
    protected $guarded = [];
    protected $casts = ['year' => 'integer', 'total_days' => 'decimal:1', 'used_days' => 'decimal:1'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function getRemainingAttribute(): float
    {
        return (float) ($this->total_days - $this->used_days);
    }
}