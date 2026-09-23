<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasHumanId;

    protected $primaryKey = 'payment_id';
    protected $idPrefix = 'PAY';
    protected $guarded = [];
    protected $casts = [
        'approval_chain' => 'array',
        'due_date' => 'date',
        'nominal' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pic_employee_id', 'employee_id');
    }
}