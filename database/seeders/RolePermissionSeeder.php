<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'dashboard.view',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'settings.view',
        'settings.update',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private array $roles = [
        'super-admin' => [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'settings.view',
            'settings.update',
        ],
        'manager' => [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'settings.view',
            'settings.update',
        ],
        'staff' => [
            'dashboard.view',
            'users.view',
        ],
        'trainer' => [
            'dashboard.view',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach ($this->roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($rolePermissions);
        }
    }
}
