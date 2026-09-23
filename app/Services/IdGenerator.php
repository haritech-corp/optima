<?php

namespace App\Services;

use App\Models\IdSequence;
use Illuminate\Support\Facades\DB;

/**
 * Generates the human-readable primary keys defined in the blueprint
 * (EMP-XXXX, CLI-XXXX, DEAL-XXXX, ...). IDs are automatic and never edited.
 */
class IdGenerator
{
    public static function next(string $prefix): string
    {
        return DB::transaction(function () use ($prefix): string {
            $sequence = IdSequence::query()->lockForUpdate()->firstOrCreate(['prefix' => $prefix]);
            $sequence->increment('last_value');

            return sprintf('%s-%04d', $prefix, $sequence->refresh()->last_value);
        });
    }
}