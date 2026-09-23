<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationRun extends Model
{
    protected $guarded = [];
    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'executed_at' => 'datetime',
    ];
}