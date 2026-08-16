<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Sidebar Menu Groups
    |--------------------------------------------------------------------------
    |
    | Each item requires a permission that exists in the database. Groups with
    | no visible items are automatically hidden.
    |
    */

    'groups' => [
        [
            'key' => 'dashboard',
            'icon' => 'speedometer2',
            'items' => [
                [
                    'key' => 'dashboard',
                    'route' => 'admin.dashboard',
                    'permission' => 'dashboard.view',
                    'match' => 'admin.dashboard',
                    'icon' => 'speedometer2',
                ],
            ],
        ],
        [
            'key' => 'administration',
            'icon' => 'shield-lock',
            'items' => [
                [
                    'key' => 'users',
                    'route' => 'admin.users.index',
                    'permission' => 'users.view',
                    'match' => 'admin.users.*',
                    'icon' => 'people',
                ],
                [
                    'key' => 'roles',
                    'route' => 'admin.roles.index',
                    'permission' => 'roles.view',
                    'match' => 'admin.roles.*',
                    'icon' => 'person-badge',
                ],
                [
                    'key' => 'permissions',
                    'route' => 'admin.permissions.index',
                    'permission' => 'permissions.view',
                    'match' => 'admin.permissions.*',
                    'icon' => 'key',
                ],
            ],
        ],
        [
            'key' => 'membership',
            'icon' => 'person-vcard',
            'items' => [
                [
                    'key' => 'members',
                    'route' => 'admin.members.index',
                    'permission' => 'members.view',
                    'match' => 'admin.members.index|admin.members.show|admin.members.edit|admin.members.create',
                    'icon' => 'people-fill',
                ],
                [
                    'key' => 'plans',
                    'route' => 'admin.membership-plans.index',
                    'permission' => 'membership-plans.view',
                    'match' => 'admin.membership-plans.*',
                    'icon' => 'card-list',
                ],
                [
                    'key' => 'registration',
                    'route' => 'admin.members.register.create',
                    'permission' => 'members.create',
                    'match' => 'admin.members.register.*',
                    'icon' => 'person-plus',
                ],
                [
                    'key' => 'renew',
                    'route' => 'admin.members.renew.create',
                    'permission' => 'members.view',
                    'match' => 'admin.members.renew.*',
                    'icon' => 'arrow-repeat',
                ],
                [
                    'key' => 'rfid',
                    'route' => 'admin.rfid-cards.index',
                    'permission' => 'rfid-cards.view',
                    'match' => 'admin.rfid-cards.*',
                    'icon' => 'credit-card-2-front',
                ],
            ],
        ],
        [
            'key' => 'finance',
            'icon' => 'cash-stack',
            'items' => [
                [
                    'key' => 'payments',
                    'route' => 'admin.payments.index',
                    'permission' => 'payments.view',
                    'match' => 'admin.payments.*',
                    'icon' => 'wallet2',
                ],
                [
                    'key' => 'invoices',
                    'route' => 'admin.invoices.index',
                    'permission' => 'payments.view',
                    'match' => 'admin.invoices.*',
                    'icon' => 'receipt',
                ],
            ],
        ],
        [
            'key' => 'inventory',
            'icon' => 'box-seam',
            'items' => [
                [
                    'key' => 'products',
                    'route' => 'admin.products.index',
                    'permission' => 'products.view',
                    'match' => 'admin.products.index|admin.products.show|admin.products.edit|admin.products.create',
                    'icon' => 'box',
                ],
                [
                    'key' => 'categories',
                    'route' => 'admin.categories.index',
                    'permission' => 'products.view',
                    'match' => 'admin.categories.*',
                    'icon' => 'tags',
                ],
                [
                    'key' => 'pos',
                    'route' => 'admin.pos.index',
                    'permission' => 'payments.create',
                    'match' => 'admin.pos.*',
                    'icon' => 'shop-window',
                ],
                [
                    'key' => 'orders',
                    'route' => 'admin.orders.index',
                    'permission' => 'payments.view',
                    'match' => 'admin.orders.*',
                    'icon' => 'bag-check',
                ],
            ],
        ],
        [
            'key' => 'reports',
            'icon' => 'bar-chart-line',
            'items' => [
                [
                    'key' => 'reports',
                    'route' => 'admin.reports.index',
                    'permission' => 'reports.view',
                    'match' => 'admin.reports.*',
                    'icon' => 'bar-chart-line',
                ],
            ],
        ],
        [
            'key' => 'settings',
            'icon' => 'gear',
            'items' => [
                [
                    'key' => 'general_settings',
                    'route' => 'admin.settings.edit',
                    'permission' => 'settings.view',
                    'match' => 'admin.settings.*',
                    'icon' => 'gear',
                ],
                [
                    'key' => 'zkteco_devices',
                    'route' => 'admin.zkteco-devices.index',
                    'permission' => 'zkteco-devices.view',
                    'match' => 'admin.zkteco-devices.*',
                    'icon' => 'fingerprint',
                ],
                [
                    'key' => 'attendance_logs',
                    'route' => 'admin.attendance-logs.index',
                    'permission' => 'attendance-logs.view',
                    'match' => 'admin.attendance-logs.*',
                    'icon' => 'clock-history',
                ],
            ],
        ],
    ],

];
