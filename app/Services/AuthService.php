<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

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

    /**
     * @throws AuthenticationException
     */
    public function devLogin(string $email): User
    {
        abort_unless(app()->isLocal(), 404);

        $user = $this->users->findByEmail($email);

        if (! $user) {
            throw new AuthenticationException('Dev login user not found. Run php artisan db:seed.');
        }

        if (! $user->isActive()) {
            throw new AuthenticationException('Your account has been deactivated.');
        }

        Auth::login($user);

        $this->activityLogger->log('auth.dev_login', $user, 'One-click local dev login');

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

    public function sendPasswordResetLink(string $email): string
    {
        $user = $this->users->findByEmail($email);

        if ($user && ! $user->isActive()) {
            return Password::RESET_LINK_SENT;
        }

        return Password::sendResetLink(['email' => $email]);
    }

    /**
     * @param  array{token: string, email: string, password: string}  $credentials
     */
    public function resetPassword(array $credentials): string
    {
        $user = $this->users->findByEmail($credentials['email']);

        if ($user && ! $user->isActive()) {
            throw new InvalidArgumentException('This account has been deactivated.');
        }

        return Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->save();

                event(new PasswordReset($user));

                $this->activityLogger->log('auth.password_reset', $user, 'Password reset via email link');
            },
        );
    }

    /**
     * @throws ValidationException
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $this->transaction(function () use ($user, $newPassword): void {
            $this->users->update($user, [
                'password' => $newPassword,
            ]);

            Auth::logoutOtherDevices($newPassword);

            $this->activityLogger->log('auth.password_changed', $user, 'Password changed from profile');
        });
    }
}
