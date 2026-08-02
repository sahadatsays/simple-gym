<?php

namespace App\Services;

use App\Support\ActivityLogger;
use App\Support\PermissionRegistry;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleService extends BaseService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * @return Collection<int, Role>
     */
    public function allWithPermissions(): Collection
    {
        return Role::query()
            ->with('permissions')
            ->withCount('permissions')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array{name: string, permissions?: array<int, string>}  $data
     */
    public function create(array $data): Role
    {
        return $this->transaction(function () use ($data): Role {
            $role = Role::query()->create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            if (! empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $this->activityLogger->log('role.created', $role, 'Role created');

            return $role->load('permissions');
        });
    }

    /**
     * @param  array{name: string, permissions?: array<int, string>}  $data
     */
    public function update(Role $role, array $data): Role
    {
        return $this->transaction(function () use ($role, $data): Role {
            $role->update(['name' => $data['name']]);

            if (array_key_exists('permissions', $data)) {
                $role->syncPermissions($data['permissions'] ?? []);
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $this->activityLogger->log('role.updated', $role, 'Role updated');

            return $role->load('permissions');
        });
    }

    public function delete(Role $role): void
    {
        if (PermissionRegistry::isProtectedRole($role->name)) {
            throw new \InvalidArgumentException('This role is protected and cannot be deleted.');
        }

        $this->transaction(function () use ($role): void {
            $this->activityLogger->log('role.deleted', $role, 'Role deleted');
            $role->delete();

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        });
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function groupedPermissions(): array
    {
        $permissions = Permission::query()->orderBy('name')->pluck('name');

        return $permissions
            ->groupBy(fn (string $name): string => explode('.', $name)[0] ?? 'general')
            ->map(fn (Collection $group) => $group->values()->all())
            ->all();
    }
}
