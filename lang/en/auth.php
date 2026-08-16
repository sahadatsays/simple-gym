<?php

return [
    'login' => [
        'title' => 'Sign in to your admin account',
        'sign_in' => 'Sign in',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot password?',
        'dev_only' => 'Local only',
        'dev_login' => 'One-click login as :name',
    ],

    'forgot' => [
        'title' => 'Forgot password',
        'subtitle' => 'Enter your email to receive a reset link',
        'send_link' => 'Send reset link',
        'back_to_sign_in' => 'Back to sign in',
        'status' => 'If an account exists for that email, a password reset link has been sent.',
    ],

    'reset' => [
        'title' => 'Reset password',
        'subtitle' => 'Choose a new password for your account',
        'submit' => 'Reset password',
        'success' => 'Your password has been reset. You can sign in now.',
    ],

    'flash' => [
        'welcome_back' => 'Welcome back!',
        'dev_login' => 'Logged in via local dev shortcut.',
        'logged_out' => 'You have been logged out.',
    ],

    'errors' => [
        'invalid_credentials' => 'These credentials do not match our records.',
        'account_deactivated' => 'Your account has been deactivated.',
        'account_deactivated_contact' => 'Your account has been deactivated. Please contact an administrator.',
        'dev_user_not_found' => 'Dev login user not found. Run php artisan db:seed.',
    ],
];
