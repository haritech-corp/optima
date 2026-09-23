<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'skey';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::query()->whereKey($key)->value('value');

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        self::updateOrCreate(['skey' => $key], ['value' => is_scalar($value) ? (string) $value : json_encode($value), 'group' => $group]);
    }
}