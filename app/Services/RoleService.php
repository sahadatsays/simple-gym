<?php

namespace App\Services;

use App\Models\Role;
use App\Support\ActivityLogger;
use App\Support\PermissionRegistry;
use Illuminate\Support\Collection;
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
            ->orderBy('display_name')
            ->get();
    }

    /**
     * @param  array{display_name: string, slug: string, permissions?: array<int, string>}  $data
     */
    public function create(array $data): Role
    {
        return $this->transaction(function () use ($data): Role {
            $role = Role::query()->create([
                'name' => $data['slug'],
                'display_name' => $data['display_name'],
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
     * @param  array{display_name: string, slug: string, permissions?: array<int, string>}  $data
     */
    public function update(Role $role, array $data): Role
    {
        return $this->transaction(function () use ($role, $data): Role {
            $isProtected = PermissionRegistry::isProtectedRole($role->name);

            $attributes = ['display_name' => $data['display_name']];

            if (! $isProtected) {
                $attributes['name'] = $data['slug'];
            }

            $role->update($attributes);

            if (! $isProtected && array_key_exists('permissions', $data)) {
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
        return PermissionRegistry::groupedForAssignment();
    }
}
