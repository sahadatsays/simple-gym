<?php

namespace App\Services;

use App\Support\ActivityLogger;
use App\Support\PermissionRegistry;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionService extends BaseService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * @return Collection<int, Permission>
     */
    public function allGrouped(): Collection
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission): string => explode('.', $permission->name)[0] ?? 'general');
    }

    public function create(string $name): Permission
    {
        return $this->transaction(function () use ($name): Permission {
            $permission = Permission::findOrCreate($name);

            PermissionRegistry::assignToSuperAdmin($permission);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $this->activityLogger->log('permission.created', $permission, 'Permission created');

            return $permission;
        });
    }

    public function update(Permission $permission, string $name): Permission
    {
        return $this->transaction(function () use ($permission, $name): Permission {
            $permission->update(['name' => $name]);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $this->activityLogger->log('permission.updated', $permission, 'Permission updated');

            return $permission;
        });
    }

    public function delete(Permission $permission): void
    {
        $this->transaction(function () use ($permission): void {
            $this->activityLogger->log('permission.deleted', $permission, 'Permission deleted');
            $permission->delete();

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        });
    }
}
