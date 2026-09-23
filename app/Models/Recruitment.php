<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recruitment extends Model
{
    use HasHumanId;

    protected $primaryKey = 'recruitment_id';
    protected $idPrefix = 'RCV';
    protected $guarded = [];
    protected $casts = ['date' => 'date', 'archived_at' => 'datetime'];

    public function requesterDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'requester_department_id', 'department_id');
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recruiter_employee_id', 'employee_id');
    }
}