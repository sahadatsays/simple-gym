<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class PermissionRegistry
{
    /**
     * @return Collection<int, string>
     */
    public static function all(): Collection
    {
        return collect(config('permissions.groups', []))
            ->flatten()
            ->unique()
            ->values();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function grouped(): array
    {
        return config('permissions.groups', []);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function groupedForAssignment(): array
    {
        $groups = self::grouped();

        $extraPermissions = Permission::query()
            ->orderBy('name')
            ->pluck('name')
            ->diff(self::all())
            ->values();

        if ($extraPermissions->isNotEmpty()) {
            $groups['custom'] = $extraPermissions->all();
        }

        return $groups;
    }

    public static function groupLabel(string $group): string
    {
        return config("permissions.group_labels.{$group}")
            ?? str($group)->replace(['-', '_'], ' ')->title()->toString();
    }

    public static function permissionLabel(string $permission): string
    {
        return config("permissions.labels.{$permission}")
            ?? str($permission)
                ->after('.')
                ->replace(['-', '_'], ' ')
                ->title()
                ->toString();
    }

    public static function syncToDatabase(): void
    {
        foreach (self::all() as $permission) {
            Permission::findOrCreate($permission);
        }
    }

    /**
     * @return array<int, string>
     */
    public static function permissionsForRole(string $roleName): array
    {
        $roles = config('permissions.roles', []);
        $permissions = $roles[$roleName] ?? [];

        if ($permissions === '*') {
            return self::all()->all();
        }

        return $permissions;
    }

    /**
     * @return array<string, string>
     */
    public static function defaultDisplayNames(): array
    {
        return config('permissions.role_display_names', []);
    }

    public static function isProtectedRole(string $roleName): bool
    {
        return in_array($roleName, config('permissions.protected_roles', []), true);
    }

    public static function assignToSuperAdmin(Permission $permission): void
    {
        $role = Role::query()->where('name', 'super-admin')->first();

        if ($role !== null) {
            $role->givePermissionTo($permission);
        }
    }
}
