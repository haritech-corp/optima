<?php

namespace App\Services;

use App\Models\EmployeeWorkload;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Keeps the PIC workload tracker in sync: active tasks enter automatically,
 * done tasks reduce the outstanding workload (blueprint project_to_workload).
 */
class WorkloadService
{
    public static function syncFor(?string $employeeId = null): void
    {
        self::compute()
            ->each(function (array $stats): void {
                EmployeeWorkload::query()->updateOrCreate(
                    ['employee_id' => $stats['employee_id']],
                    [
                        'active_tasks' => $stats['active'],
                        'overdue_tasks' => $stats['overdue'],
                        'done_tasks_30d' => $stats['done30'],
                        'total_load' => $stats['load'],
                        'synced_at' => now(),
                    ],
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function compute(?string $employeeId = null): Collection
    {
        $tasks = Task::query()
            ->select(['pic_employee_id', 'status', 'is_completed', 'deadline', 'completed_at'])
            ->whereNotNull('pic_employee_id')
            ->get();

        $byEmployee = $tasks->groupBy('pic_employee_id');

        return $byEmployee
            ->map(function (Collection $tasks, string $employeeId) {
                $active = $tasks->whereNotIn('status', ['Done', 'Cancelled'])->count();
                $overdue = $tasks->filter(fn (Task $t) => ! $t->is_completed && $t->deadline && $t->deadline->lt(today()))->count();
                $done30 = $tasks->filter(fn (Task $t) => $t->completed_at && $t->completed_at->gte(now()->subDays(30)))->count();

                return [
                    'employee_id' => $employeeId,
                    'active' => $active,
                    'overdue' => $overdue,
                    'done30' => $done30,
                    'load' => min(100, $active * 10 + $overdue * 15),
                ];
            })
            ->when($employeeId, fn (Collection $c) => $c->where('employee_id', $employeeId))
            ->values();
    }
}