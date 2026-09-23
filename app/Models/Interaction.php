<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    use HasHumanId;

    protected $primaryKey = 'interaction_id';
    protected $idPrefix = 'INT';
    protected $guarded = [];
    protected $casts = [
        'interaction_date' => 'datetime',
        'follow_up_date' => 'date',
    ];

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pic_employee_id', 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by', 'employee_id');
    }

    public function entity(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    public static function entityLabel(string $type): string
    {
        return match ($type) {
            'lead' => 'Lead',
            'client' => 'Client',
            'community' => 'Community',
            'kol_koc' => 'KOL/KOC',
            'media' => 'Media',
            'vendor' => 'Vendor',
            'deal' => 'Deal',
            'project' => 'Project',
            'campaign' => 'Campaign',
            default => $type,
        };
    }
}