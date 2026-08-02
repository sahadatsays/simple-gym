<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService extends BaseService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return $this->transaction(function () use ($data): User {
            $role = $data['role'];
            unset($data['role']);

            $data['password'] = Hash::make($data['password']);

            $user = $this->users->create($data);
            $user->syncRoles([$role]);

            $this->activityLogger->log('user.created', $user, 'User account created', [
                'role' => $role,
                'username' => $user->username,
            ]);

            return $user->load('roles');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return $this->transaction(function () use ($user, $data): User {
            $role = $data['role'] ?? null;
            unset($data['role'], $data['password'], $data['password_confirmation']);

            $updatedUser = $this->users->update($user, $data);

            if ($role !== null) {
                $updatedUser->syncRoles([$role]);
            }

            $this->activityLogger->log('user.updated', $updatedUser, 'User account updated');

            return $updatedUser->load('roles');
        });
    }

    public function delete(User $user): void
    {
        $this->transaction(function () use ($user): void {
            $this->users->update($user, [
                'username' => $user->username.'-deleted-'.$user->id,
                'email' => 'deleted-'.$user->id.'@deleted.local',
            ]);

            $this->activityLogger->log('user.deleted', $user, 'User account deleted');
            $this->users->delete($user);
        });
    }

    public function activate(User $user): User
    {
        return $this->transaction(function () use ($user): User {
            $updatedUser = $this->users->update($user, ['is_active' => true]);

            $this->activityLogger->log('user.activated', $updatedUser, 'User account activated');

            return $updatedUser;
        });
    }

    public function deactivate(User $user): User
    {
        return $this->transaction(function () use ($user): User {
            $updatedUser = $this->users->update($user, ['is_active' => false]);

            $this->activityLogger->log('user.deactivated', $updatedUser, 'User account deactivated');

            return $updatedUser;
        });
    }

    public function resetPassword(User $user, string $password): User
    {
        return $this->transaction(function () use ($user, $password): User {
            $updatedUser = $this->users->update($user, [
                'password' => Hash::make($password),
            ]);

            $this->activityLogger->log('user.password_reset', $updatedUser, 'User password reset by admin');

            return $updatedUser;
        });
    }

    /**
     * @return array<int, string>
     */
    public function assignableRoles(): array
    {
        return Role::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
