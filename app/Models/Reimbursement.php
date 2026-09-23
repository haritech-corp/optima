<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reimbursement extends Model
{
    use HasHumanId;

    protected $primaryKey = 'reimbursement_id';
    protected $idPrefix = 'RMB';
    protected $guarded = [];
    protected $casts = ['date' => 'date', 'nominal' => 'decimal:2', 'paid_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}