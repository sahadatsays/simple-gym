<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Super Admin Account
    |--------------------------------------------------------------------------
    |
    | Used when seeding the initial super-admin user in production and
    | development environments. Set ADMIN_PASSWORD in production.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Super Admin'),
        'username' => env('ADMIN_USERNAME', 'superadmin'),
        'email' => env('ADMIN_EMAIL', env('DEV_LOGIN_EMAIL', 'admin@simplegym.test')),
        'password' => env('ADMIN_PASSWORD'),
        'phone' => env('ADMIN_PHONE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo Data Toggle
    |--------------------------------------------------------------------------
    |
    | When true, demo seeders run even outside local/development environments.
    | By default, demo data is only seeded in local, development, and testing.
    |
    */

    'seed_demo_data' => env('SEED_DEMO_DATA', false),

    /*
    |--------------------------------------------------------------------------
    | Demo Users
    |--------------------------------------------------------------------------
    |
    | Additional accounts seeded only in development mode.
    |
    */

    'demo_users' => [
        [
            'name' => 'Gym Manager',
            'username' => 'manager',
            'email' => 'manager@simplegym.test',
            'password' => 'password',
            'role' => 'manager',
        ],
        [
            'name' => 'Front Desk Staff',
            'username' => 'staff',
            'email' => 'staff@simplegym.test',
            'password' => 'password',
            'role' => 'staff',
        ],
        [
            'name' => 'Fitness Trainer',
            'username' => 'trainer',
            'email' => 'trainer@simplegym.test',
            'password' => 'password',
            'role' => 'trainer',
        ],
    ],

];
