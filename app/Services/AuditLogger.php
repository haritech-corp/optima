<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Immutable audit trail. Records every important field change with actor,
 * record, old value and new value (blueprint data governance rule).
 */
class AuditLogger
{
    public static function log(
        string $recordType,
        string $recordId,
        string $fieldChanged,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $userId = null,
        ?string $ipAddress = null,
    ): void {
        AuditLog::create([
            'user_id' => $userId,
            'record_type' => $recordType,
            'record_id' => $recordId,
            'field_changed' => $fieldChanged,
            'old_value' => self::stringify($oldValue),
            'new_value' => self::stringify($newValue),
            'ip_address' => $ipAddress ?? request()->ip(),
        ]);
    }

    /**
     * Audit a status transition: previous status, new status, actor, timestamp.
     */
    public static function statusChange(
        string $recordType,
        string $recordId,
        string $oldStatus,
        string $newStatus,
        ?string $userId = null,
    ): void {
        self::log($recordType, $recordId, 'status', $oldStatus, $newStatus, $userId);
    }

    /** Audit all visible differences between an old and a new model snapshot. */
    public static function changed(Model $model, array $oldAttributes, ?string $userId = null): void
    {
        $recordType = class_basename($model);
        $recordId = (string) $model->getKey();
        foreach ($model->getDirty() as $field => $newValue) {
            $oldValue = $oldAttributes[$field] ?? null;
            if ($oldValue === $newValue) {
                continue;
            }
            self::log($recordType, $recordId, $field, $oldValue, $newValue, $userId);
        }
    }

    private static function stringify(mixed $value): ?string
    {
        if ($value === null || is_scalar($value)) {
            return $value === null ? null : (string) $value;
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}