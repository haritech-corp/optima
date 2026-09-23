<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasHumanId;

    protected $primaryKey = 'employee_id';
    protected $idPrefix = 'EMP';
    protected $guarded = [];
    protected $casts = [
        'start_date' => 'date',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_employee_id', 'employee_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_employee_id', 'employee_id');
    }

    public function accessRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'access_role', 'role_id');
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'employee_id', 'employee_id');
    }

    /** Members of the finance team (Business Operation – Finance). */
    public static function financeTeam(): \Illuminate\Support\Collection
    {
        return self::query()
            ->whereHas('department', fn ($q) => $q->whereIn('code', ['OPT-BOF', 'OPT-FIN', 'FIN', 'BO-FIN']))
            ->orWhereHas('accessRole', fn ($q) => $q->whereIn('role_id', ['finance', 'finance_admin']))
            ->get()
            ->unique('employee_id');
    }

    protected static function booted(): void
    {
        static::saving(function (Employee $employee): void {
            $employee->is_active = $employee->status === 'active';
        });
    }
}