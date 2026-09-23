<?php

namespace App\Services;

use App\Models\AutomationLog;

/**
 * Records every automation execution with success/failure status and retry
 * count — required by the blueprint's data governance rules.
 */
class AutomationLogger
{
    public static function log(
        string $automationName,
        string $recordType,
        string $recordId,
        string $status = 'success',
        ?string $errorMessage = null,
        int $retryCount = 0,
    ): void {
        AutomationLog::create([
            'automation_name' => $automationName,
            'record_type' => $recordType,
            'record_id' => $recordId,
            'status' => $status,
            'error_message' => $errorMessage,
            'retry_count' => $retryCount,
        ]);
    }

    /** Wrap a callable in automation logging with retry-able failures. */
    public static function run(
        string $automationName,
        string $recordType,
        string $recordId,
        callable $callback,
    ): mixed {
        try {
            $result = $callback();
            self::log($automationName, $recordType, $recordId, 'success');

            return $result;
        } catch (\Throwable $e) {
            self::log($automationName, $recordType, $recordId, 'failed', $e->getMessage());
            report($e);
            throw $e;
        }
    }
}