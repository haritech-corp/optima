<?php

namespace App\Services;

use App\Models\RolePermission;
use Illuminate\Database\Eloquent\Builder;

/**
 * Role & access control helper. Reads the authenticated user from the session
 * (established by AuthController after Supabase authentication) and applies
 * the RBAC matrix defined in the blueprint.
 */
class AccessService
{
    public const SUPER_ADMIN_GLOBAL = 'super_admin_global';
    public const SUPER_ADMIN_DEPARTMENT = 'super_admin_department';
    public const KOORDINATOR = 'koordinator';
    public const STAFF = 'staff';
    public const VIEWER = 'viewer';

    public static function user(): ?array
    {
        return session('optima_user');
    }

    public static function employeeId(): ?string
    {
        return data_get(self::user(), 'employee_id');
    }

    public static function role(): ?string
    {
        return data_get(self::user(), 'role', self::STAFF);
    }

    public static function departmentId(): ?string
    {
        return data_get(self::user(), 'department_id');
    }

    public static function isSuperAdminGlobal(): bool
    {
        return self::role() === self::SUPER_ADMIN_GLOBAL;
    }

    /**
     * permission_id => scope for the given role.
     *
     * @return array<string, string>
     */
    public static function permissionMap(?string $role = null): array
    {
        $role ??= self::role();

        return cache()->remember("optima.rolemap.{$role}", 3600, function () use ($role): array {
            return RolePermission::query()
                ->where('role_id', $role)
                ->pluck('scope', 'permission_id')
                ->all();
        });
    }

    /** Whether the current user may perform the given module action. */
    public static function can(string $permission, bool $requireScope = false): bool
    {
        $role = self::role();
        if ($role === self::SUPER_ADMIN_GLOBAL) {
            return true;
        }
        if ($role === self::SUPER_ADMIN_DEPARTMENT && str_starts_with($permission, 'admin.')) {
            return false; // only global admin manages global-level administration
        }
        $map = self::permissionMap($role);

        if ($requireScope) {
            return ($map[$permission] ?? null) === 'all';
        }

        return array_key_exists($permission, $map);
    }

    public static function scopeFor(string $permission): ?string
    {
        $scope = self::permissionMap()[ $permission ] ?? null;
        if ($scope === 'all') {
            return 'all';
        }
        if ($scope === 'department') {
            return 'department';
        }
        if ($scope === 'own') {
            return 'own';
        }

        return null;
    }

    /** A shortcut used by views: @can over a module permission. */
    public static function vcan(string $permission): bool
    {
        return self::can($permission);
    }

    /** Apply department scope to a query on the given employee column. */
    public static function scopeByDepartment(Builder $query, string $employeeColumn): Builder
    {
        $department = self::departmentId();
        if (! $department || self::isSuperAdminGlobal()) {
            return $query;
        }
        if (self::role() === self::VIEWER && ! $department) {
            return $query;
        }

        return $query->whereHas('picEmployee', fn ($q) => $q->where('department_id', $department))
            ->orWhereDoesntHave('picEmployee'); // orphan records stay readable
    }

    /** Apply "own records only" scope on the given employee column. */
    public static function scopeByOwn(Builder $query, string $employeeColumn): Builder
    {
        $me = self::employeeId();

        return $query->where(function ($q) use ($employeeColumn, $me) {
            $q->where($employeeColumn, $me)->orWhereNull($employeeColumn);
        });
    }

    /**
     * Decide how a module list should be scoped for the current user.
     * Guard against SQLite "whereHas on column" issues by using explicit joins.
     */
    public static function moduleScope(string $module): string
    {
        $role = self::role();
        if ($role === self::SUPER_ADMIN_GLOBAL) {
            return 'all';
        }
        // Department holder of the module sees everything inside the department.
        $map = self::permissionMap($role);
        $view = "{$module}.view";
        if (array_key_exists($view, $map)) {
            return $map[$view];
        }

        return in_array($role, [self::SUPER_ADMIN_DEPARTMENT, self::KOORDINATOR], true) ? 'department' : 'own';
    }
}