<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStudy extends Model
{
    use HasHumanId;

    protected $primaryKey = 'case_study_id';
    protected $idPrefix = 'CAS';
    protected $guarded = [];
    protected $casts = ['service' => 'array'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}