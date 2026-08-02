<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Services\RoleService;
use App\Support\Flash;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private RoleService $roleService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', [
            'roles' => $this->roleService->allWithPermissions(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.create', [
            'groupedPermissions' => $this->roleService->groupedPermissions(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $this->roleService->create([
            'name' => $request->validated('name'),
            'permissions' => $request->validated('permissions', []),
        ]);

        Flash::success('Role created successfully.');

        return redirect()->route('admin.roles.index');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'groupedPermissions' => $this->roleService->groupedPermissions(),
            'isProtected' => PermissionRegistry::isProtectedRole($role->name),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $this->roleService->update($role, [
            'name' => $request->validated('name'),
            'permissions' => $request->validated('permissions', []),
        ]);

        Flash::success('Role updated successfully.');

        return redirect()->route('admin.roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        try {
            $this->roleService->delete($role);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['role' => $exception->getMessage()]);
        }

        Flash::success('Role deleted successfully.');

        return redirect()->route('admin.roles.index');
    }
}
