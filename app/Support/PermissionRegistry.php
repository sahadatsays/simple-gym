<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
