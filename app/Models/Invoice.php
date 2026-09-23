<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasHumanId;

    protected $primaryKey = 'invoice_id';
    protected $idPrefix = 'INV';
    protected $guarded = [];
    protected $casts = ['nominal' => 'decimal:2', 'due_date' => 'date', 'synced_at' => 'datetime'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(InvoiceRequest::class, 'invoice_request_id', 'invoice_request_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'invoice_id');
    }
}