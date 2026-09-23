<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusMaster extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public static function options(string $category): array
    {
        return self::query()
            ->where('category', $category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('label', 'status_key')
            ->all();
    }

    public static function labels(string $category): array
    {
        return self::query()
            ->where('category', $category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('label', 'status_key')
            ->all();
    }
}