<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaign extends Model
{
    use HasHumanId;

    protected $primaryKey = 'campaign_id';
    protected $idPrefix = 'CMP';
    protected $guarded = [];
    protected $casts = [
        'channel' => 'array',
        'date' => 'date',
        'archived_at' => 'datetime',
    ];

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pic_employee_id', 'employee_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'campaign_id', 'campaign_id');
    }
}