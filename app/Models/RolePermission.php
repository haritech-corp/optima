<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RolePermission extends Pivot
{
    protected $table = 'role_permission';
    public $timestamps = false;
    protected $casts = [];
    protected $guarded = [];
}