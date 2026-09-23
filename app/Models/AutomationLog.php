<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationLog extends Model
{
    protected $guarded = [];
    protected $casts = ['triggered_at' => 'datetime', 'retry_count' => 'integer'];
}