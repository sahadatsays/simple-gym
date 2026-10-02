<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'lockers.view',
        'lockers.create',
        'lockers.edit',
        'lockers.delete',
    ];

    public function up(): void
    {
        Schema::create('lockers', function (Blueprint $table) {
            $table->id();
            $table->string('locker_number')->unique();
            $table->string('location')->nullable();
            $table->string('category')->nullable();
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->string('status')->default('available')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $grants = [
            'super-admin' => $this->permissions,
            'manager' => $this->permissions,
            'staff' => ['lockers.view'],
        ];

        foreach ($grants as $roleName => $permissions) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role !== null) {
                $role->givePermissionTo($permissions);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lockers');

        if (! Schema::hasTable('permissions')) {
            return;
        }

        Permission::query()->whereIn('name', $this->permissions)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
