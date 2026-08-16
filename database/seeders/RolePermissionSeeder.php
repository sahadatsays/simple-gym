<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        PermissionRegistry::syncToDatabase();

        $displayNames = PermissionRegistry::defaultDisplayNames();

        foreach (config('permissions.roles', []) as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName);
            $role->update([
                'display_name' => $displayNames[$roleName] ?? str($roleName)->replace('-', ' ')->title()->toString(),
            ]);
            $role->syncPermissions(PermissionRegistry::permissionsForRole($roleName));
        }
    }
}
