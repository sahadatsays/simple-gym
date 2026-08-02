<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\AuthService;
use App\Services\ProfileService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService,
        private AuthService $authService,
    ) {}

    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->profileService->update($request->user(), $request->validated());

        Flash::success('Profile updated successfully.');

        return redirect()->route('profile.edit');
    }

    public function editPassword(): View
    {
        return view('profile.password');
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $this->authService->changePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password'),
        );

        $request->session()->regenerate();

        Flash::success('Password changed successfully.');

        return redirect()->route('profile.password.edit');
    }
}
