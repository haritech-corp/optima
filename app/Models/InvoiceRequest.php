<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceRequest extends Model
{
    use HasHumanId;

    protected $primaryKey = 'invoice_request_id';
    protected $idPrefix = 'IRQ';
    protected $guarded = [];
    protected $casts = ['nominal' => 'decimal:2', 'date' => 'date'];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_employee_id', 'employee_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}