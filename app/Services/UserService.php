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
            unset($data['role']);

            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }

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
            $this->activityLogger->log('user.deleted', $user, 'User account deleted');
            $this->users->delete($user);
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
