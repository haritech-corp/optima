<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdSequence extends Model
{
    protected $primaryKey = 'prefix';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['last_value' => 'integer'];

    public function getNextValue(): int
    {
        return $this->last_value + 1;
    }
}