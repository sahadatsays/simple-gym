<?php

namespace Database\Seeders;

use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        PermissionRegistry::syncToDatabase();

        foreach (config('permissions.roles', []) as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions(PermissionRegistry::permissionsForRole($roleName));
        }
    }
}
