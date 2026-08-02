<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService extends BaseService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array{email: string, password: string, remember?: bool}  $credentials
     *
     * @throws AuthenticationException
     */
    public function attemptLogin(array $credentials): User
    {
        $user = $this->users->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('These credentials do not match our records.');
        }

        if (! $user->isActive()) {
            throw new AuthenticationException('Your account has been deactivated.');
        }

        Auth::login($user, $credentials['remember'] ?? false);

        $this->activityLogger->log('auth.login', $user, 'User logged in');

        return $user;
    }

    public function logout(): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $this->activityLogger->log('auth.logout', $user, 'User logged out');
        }

        Auth::logout();
    }
}
