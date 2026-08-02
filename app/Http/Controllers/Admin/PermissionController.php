<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Services\PermissionService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(private PermissionService $permissionService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Permission::class);

        return view('admin.permissions.index', [
            'groupedPermissions' => $this->permissionService->allGrouped(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Permission::class);

        return view('admin.permissions.create');
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $this->authorize('create', Permission::class);

        $this->permissionService->create($request->validated('name'));

        Flash::success('Permission created successfully.');

        return redirect()->route('admin.permissions.index');
    }

    public function edit(Permission $permission): View
    {
        $this->authorize('update', $permission);

        return view('admin.permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $this->authorize('update', $permission);

        $this->permissionService->update($permission, $request->validated('name'));

        Flash::success('Permission updated successfully.');

        return redirect()->route('admin.permissions.index');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->authorize('delete', $permission);

        $this->permissionService->delete($permission);

        Flash::success('Permission deleted successfully.');

        return redirect()->route('admin.permissions.index');
    }
}
