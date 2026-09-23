<?php

namespace App\Models\Concerns;

use App\Services\IdGenerator;

/**
 * Auto-generates human-readable primary keys (EMP-XXXX, DEAL-XXXX, …) on
 * creation. Keys are never edited manually (blueprint principle).
 */
trait HasHumanId
{
    protected static function bootHasHumanId(): void
    {
        static::creating(function ($model): void {
            if (empty($model->getKey())) {
                $model->setAttribute($model->getKeyName(), IdGenerator::next($model->idPrefix ?? 'REC'));
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}