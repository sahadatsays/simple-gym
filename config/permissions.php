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
            'payments.delete',
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
        'investments' => [
            'investments.view',
            'investments.create',
            'investments.edit',
            'investments.delete',
        ],
        'assets' => [
            'assets.view',
            'assets.create',
            'assets.edit',
            'assets.delete',
        ],
        'asset-categories' => [
            'asset-categories.view',
            'asset-categories.create',
            'asset-categories.edit',
            'asset-categories.delete',
        ],
        'asset-maintenances' => [
            'asset-maintenances.view',
            'asset-maintenances.create',
            'asset-maintenances.edit',
            'asset-maintenances.delete',
        ],
        'asset-disposals' => [
            'asset-disposals.view',
            'asset-disposals.create',
        ],
        'asset-investment-reports' => [
            'asset-investment-reports.view',
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

    'group_labels' => [
        'dashboard' => 'Dashboard',
        'users' => 'Users',
        'members' => 'Members',
        'membership-plans' => 'Membership Plans',
        'rfid-cards' => 'RFID Cards',
        'payments' => 'Payments & POS',
        'products' => 'Products & Inventory',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'zkteco-devices' => 'ZKTeco Devices',
        'attendance-logs' => 'Attendance Logs',
        'investments' => 'Investments',
        'assets' => 'Assets',
        'asset-categories' => 'Asset Categories',
        'asset-maintenances' => 'Asset Maintenance',
        'asset-disposals' => 'Asset Disposal',
        'asset-investment-reports' => 'Asset & Investment Reports',
        'roles' => 'Roles',
        'permissions' => 'Permissions',
        'custom' => 'Custom Permissions',
    ],

    'labels' => [
        'dashboard.view' => 'View dashboard',
        'users.view' => 'View users',
        'users.create' => 'Create users',
        'users.update' => 'Update users',
        'users.delete' => 'Delete users',
        'members.view' => 'View members',
        'members.create' => 'Register members',
        'members.edit' => 'Edit & renew members',
        'members.delete' => 'Delete members',
        'membership-plans.view' => 'View plans',
        'membership-plans.create' => 'Create plans',
        'membership-plans.edit' => 'Edit plans',
        'membership-plans.delete' => 'Delete plans',
        'rfid-cards.view' => 'View RFID cards',
        'rfid-cards.manage' => 'Manage RFID cards',
        'payments.view' => 'View payments & invoices',
        'payments.create' => 'Create payments & use POS',
        'payments.delete' => 'Delete same-day POS orders',
        'products.view' => 'View products & categories',
        'products.manage' => 'Manage products & stock',
        'reports.view' => 'View reports',
        'settings.view' => 'View settings',
        'settings.update' => 'Update settings',
        'zkteco-devices.view' => 'View devices',
        'zkteco-devices.manage' => 'Manage devices',
        'attendance-logs.view' => 'View attendance logs',
        'investments.view' => 'View investments',
        'investments.create' => 'Create investments',
        'investments.edit' => 'Edit investments',
        'investments.delete' => 'Delete investments',
        'assets.view' => 'View assets',
        'assets.create' => 'Create assets',
        'assets.edit' => 'Edit assets',
        'assets.delete' => 'Delete assets',
        'asset-categories.view' => 'View asset categories',
        'asset-categories.create' => 'Create asset categories',
        'asset-categories.edit' => 'Edit asset categories',
        'asset-categories.delete' => 'Delete asset categories',
        'asset-maintenances.view' => 'View asset maintenance',
        'asset-maintenances.create' => 'Create asset maintenance',
        'asset-maintenances.edit' => 'Edit asset maintenance',
        'asset-maintenances.delete' => 'Delete asset maintenance',
        'asset-disposals.view' => 'View asset disposals',
        'asset-disposals.create' => 'Create asset disposals',
        'asset-investment-reports.view' => 'View asset & investment reports',
        'roles.view' => 'View roles',
        'roles.create' => 'Create roles',
        'roles.update' => 'Update roles',
        'roles.delete' => 'Delete roles',
        'permissions.view' => 'View permissions',
        'permissions.create' => 'Create permissions',
        'permissions.update' => 'Update permissions',
        'permissions.delete' => 'Delete permissions',
    ],

    'role_display_names' => [
        'super-admin' => 'Super Admin',
        'manager' => 'Manager',
        'staff' => 'Staff',
        'trainer' => 'Trainer',
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
            'payments.delete',
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
