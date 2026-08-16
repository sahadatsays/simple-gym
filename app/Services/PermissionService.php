<?php

namespace App\Services;

use App\Support\ActivityLogger;
use App\Support\PermissionRegistry;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionService extends BaseService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * @return array<string, Collection<int, Permission>>
     */
    public function allGrouped(): array
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->keyBy('name');

        $grouped = collect(PermissionRegistry::grouped())
            ->map(fn (array $names): Collection => collect($names)
                ->map(fn (string $name): ?Permission => $permissions->get($name))
                ->filter()
                ->values());

        $extraPermissions = $permissions->keys()
            ->diff(PermissionRegistry::all())
            ->values()
            ->map(fn (string $name): Permission => $permissions->get($name));

        if ($extraPermissions->isNotEmpty()) {
            $grouped->put('custom', $extraPermissions);
        }

        return $grouped->all();
    }

    public function create(string $name): Permission
    {
        if (PermissionRegistry::isDefault($name)) {
            throw new InvalidArgumentException('System default permissions cannot be created manually. Run the permission seeder to sync defaults.');
        }

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
        if (PermissionRegistry::isDefault($permission->name)) {
            throw new InvalidArgumentException('System default permissions cannot be updated.');
        }

        if (PermissionRegistry::isDefault($name)) {
            throw new InvalidArgumentException('A permission name reserved for system defaults cannot be used.');
        }

        return $this->transaction(function () use ($permission, $name): Permission {
            $permission->update(['name' => $name]);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $this->activityLogger->log('permission.updated', $permission, 'Permission updated');

            return $permission;
        });
    }

    public function delete(Permission $permission): void
    {
        if (PermissionRegistry::isDefault($permission->name)) {
            throw new InvalidArgumentException('System default permissions cannot be deleted.');
        }

        $this->transaction(function () use ($permission): void {
            $this->activityLogger->log('permission.deleted', $permission, 'Permission deleted');
            $permission->delete();

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        });
    }
}
