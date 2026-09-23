<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $primaryKey = 'service_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function templates(): HasMany
    {
        return $this->hasMany(ServiceTemplate::class, 'service_id', 'service_id')->orderBy('sequence');
    }
}