<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Application Permissions
    |--------------------------------------------------------------------------
    |
    | Used only for initial seeding and syncing. Runtime authorization always
    | reads permissions from the database.
    |
    */

    'groups' => [
        'dashboard' => [
            'dashboard.view',
        ],
        'users' => [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
        ],
        'members' => [
            'members.view',
            'members.create',
            'members.edit',
            'members.delete',
        ],
        'membership-plans' => [
            'membership-plans.view',
            'membership-plans.create',
            'membership-plans.edit',
            'membership-plans.delete',
        ],
        'rfid-cards' => [
            'rfid-cards.view',
            'rfid-cards.manage',
        ],
        'payments' => [
            'payments.view',
            'payments.create',
        ],
        'products' => [
            'products.view',
            'products.manage',
        ],
        'reports' => [
            'reports.view',
        ],
        'settings' => [
            'settings.view',
            'settings.update',
        ],
        'zkteco-devices' => [
            'zkteco-devices.view',
            'zkteco-devices.manage',
        ],
        'attendance-logs' => [
            'attendance-logs.view',
        ],
        'roles' => [
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
        ],
        'permissions' => [
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role Assignments
    |--------------------------------------------------------------------------
    */

    'roles' => [
        'super-admin' => '*',
        'manager' => [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'members.view',
            'members.create',
            'members.edit',
            'membership-plans.view',
            'membership-plans.create',
            'membership-plans.edit',
            'rfid-cards.view',
            'rfid-cards.manage',
            'payments.view',
            'payments.create',
            'products.view',
            'products.manage',
            'reports.view',
            'settings.view',
            'settings.update',
            'zkteco-devices.view',
            'zkteco-devices.manage',
            'attendance-logs.view',
        ],
        'staff' => [
            'dashboard.view',
            'members.view',
            'rfid-cards.view',
            'payments.view',
            'payments.create',
            'products.view',
            'reports.view',
            'attendance-logs.view',
        ],
        'trainer' => [
            'dashboard.view',
            'members.view',
            'reports.view',
        ],
    ],

    'protected_roles' => [
        'super-admin',
    ],

];
