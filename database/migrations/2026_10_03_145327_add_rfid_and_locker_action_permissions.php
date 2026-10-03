<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $rfidActions = [
        'rfid-cards.create',
        'rfid-cards.edit',
        'rfid-cards.delete',
        'rfid-cards.assign',
        'rfid-cards.return',
        'rfid-cards.replace',
    ];

    /**
     * @var array<int, string>
     */
    private array $lockerActions = [
        'lockers.reserve',
        'lockers.renew',
        'lockers.cancel',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([...$this->rfidActions, ...$this->lockerActions] as $permission) {
            Permission::findOrCreate($permission);
        }

        $superAdmin = Role::query()->where('name', 'super-admin')->first();

        if ($superAdmin !== null) {
            $superAdmin->givePermissionTo([...$this->rfidActions, ...$this->lockerActions]);
        }

        $this->grantToRolesWith('rfid-cards.manage', $this->rfidActions);
        $this->grantToRolesWith('lockers.create', ['lockers.reserve', 'lockers.renew']);
        $this->grantToRolesWith('lockers.edit', ['lockers.cancel']);

        Permission::query()->where('name', 'rfid-cards.manage')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $manage = Permission::findOrCreate('rfid-cards.manage');

        foreach (['super-admin', 'manager'] as $roleName) {
            Role::query()->where('name', $roleName)->first()?->givePermissionTo($manage);
        }

        Permission::query()->whereIn('name', [...$this->rfidActions, ...$this->lockerActions])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function grantToRolesWith(string $existingPermission, array $permissions): void
    {
        if (! Permission::query()->where('name', $existingPermission)->exists()) {
            return;
        }

        Role::query()
            ->whereHas('permissions', fn ($query) => $query->where('name', $existingPermission))
            ->each(function (Role $role) use ($permissions): void {
                $role->givePermissionTo($permissions);
            });
    }
};
