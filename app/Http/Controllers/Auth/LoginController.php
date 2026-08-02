<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Support\Flash;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function create(): View
    {
        $devLogin = null;

        if (app()->isLocal()) {
            $email = config('gym.dev_login.email');
            $user = User::query()->where('email', $email)->first();

            $devLogin = [
                'email' => $email,
                'name' => $user?->name ?? 'Super Admin',
            ];
        }

        return view('auth.login', compact('devLogin'));
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        try {
            $this->authService->attemptLogin([
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'remember' => $request->boolean('remember'),
            ]);
        } catch (AuthenticationException $exception) {
            $request->hitRateLimiter();

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => $exception->getMessage()]);
        }

        $request->session()->regenerate();
        $request->clearRateLimiter();

        Flash::success('Welcome back!');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function devLogin(Request $request): RedirectResponse
    {
        abort_unless(app()->isLocal(), 404);

        try {
            $this->authService->devLogin(config('gym.dev_login.email'));
        } catch (AuthenticationException $exception) {
            return back()->withErrors(['email' => $exception->getMessage()]);
        }

        $request->session()->regenerate();

        Flash::success('Logged in via local dev shortcut.');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Flash::success('You have been logged out.');

        return redirect()->route('login');
    }
}
