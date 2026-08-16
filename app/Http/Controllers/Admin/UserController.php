<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexUserRequest;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserService $userService,
    ) {}

    public function index(IndexUserRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.users.index', [
            'users' => $this->users->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'roles' => $this->userService->assignableRoles(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => $this->userService->assignableRoles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $this->userService->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        Flash::success(__('flash.user.created'));

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => $this->userService->assignableRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->userService->update($user, [
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        Flash::success(__('flash.user.updated'));

        return redirect()->route('admin.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        Flash::success(__('flash.user.deleted'));

        return redirect()->route('admin.users.index');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->userService->activate($user);

        Flash::success(__('flash.user.activated'));

        return back();
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->userService->deactivate($user);

        Flash::success(__('flash.user.deactivated'));

        return back();
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->userService->resetPassword($user, $request->validated('password'));

        Flash::success(__('flash.user.password_reset'));

        return back();
    }
}
