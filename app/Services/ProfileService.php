<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Support\ActivityLogger;

class ProfileService extends BaseService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: string|null}  $data
     */
    public function update(User $user, array $data): User
    {
        return $this->transaction(function () use ($user, $data): User {
            $updatedUser = $this->users->update($user, $data);

            $this->activityLogger->log('profile.updated', $updatedUser, 'Profile updated');

            return $updatedUser;
        });
    }
}
