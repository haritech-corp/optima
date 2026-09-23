<?php

namespace App\Services;

use App\Models\Notification;

/**
 * In-app notification sink. External channels (email, Make.com webhook) are
 * pluggable via config('optima.notification_channels').
 */
class NotificationService
{
    public static function send(
        string $employeeId,
        string $title,
        ?string $body = null,
        ?string $link = null,
        string $type = 'info',
    ): Notification {
        return Notification::create([
            'employee_id' => $employeeId,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'type' => $type,
        ]);
    }

    /** Notify the manager of an employee (used for approvals, escalations). */
    public static function notifyManagerOf(string $employeeId, string $title, ?string $body = null, ?string $link = null): void
    {
        $managerId = \App\Models\Employee::query()->whereKey($employeeId)->value('manager_employee_id');
        if ($managerId) {
            self::send($managerId, $title, $body, $link);
        }
    }
}