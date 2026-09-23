<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $guarded = [];
    protected $casts = ['read_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public static function unreadCount(?string $employeeId): int
    {
        if (! $employeeId) {
            return 0;
        }

        return self::query()->where('employee_id', $employeeId)->unread()->count();
    }
}