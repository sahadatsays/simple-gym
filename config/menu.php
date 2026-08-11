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
            'label' => 'Dashboard',
            'icon' => 'speedometer2',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'admin.dashboard',
                    'permission' => 'dashboard.view',
                    'match' => 'admin.dashboard',
                    'icon' => 'speedometer2',
                ],
            ],
        ],
        [
            'key' => 'administration',
            'label' => 'Administration',
            'icon' => 'shield-lock',
            'items' => [
                [
                    'label' => 'Users',
                    'route' => 'admin.users.index',
                    'permission' => 'users.view',
                    'match' => 'admin.users.*',
                    'icon' => 'people',
                ],
                [
                    'label' => 'Roles',
                    'route' => 'admin.roles.index',
                    'permission' => 'roles.view',
                    'match' => 'admin.roles.*',
                    'icon' => 'person-badge',
                ],
            ],
        ],
        [
            'key' => 'membership',
            'label' => 'Membership',
            'icon' => 'person-vcard',
            'items' => [
                [
                    'label' => 'Members',
                    'route' => 'admin.members.index',
                    'permission' => 'members.view',
                    'match' => 'admin.members.index|admin.members.show|admin.members.edit|admin.members.create',
                    'icon' => 'people-fill',
                ],
                [
                    'label' => 'Plans',
                    'route' => 'admin.membership-plans.index',
                    'permission' => 'membership-plans.view',
                    'match' => 'admin.membership-plans.*',
                    'icon' => 'card-list',
                ],
                [
                    'label' => 'Registration',
                    'route' => 'admin.members.register.create',
                    'permission' => 'members.create',
                    'match' => 'admin.members.register.*',
                    'icon' => 'person-plus',
                ],
                [
                    'label' => 'Renew',
                    'route' => 'admin.members.renew.create',
                    'permission' => 'members.view',
                    'match' => 'admin.members.renew.*',
                    'icon' => 'arrow-repeat',
                ],
                [
                    'label' => 'RFID',
                    'route' => 'admin.rfid-cards.index',
                    'permission' => 'rfid-cards.view',
                    'match' => 'admin.rfid-cards.*',
                    'icon' => 'credit-card-2-front',
                ],
            ],
        ],
        [
            'key' => 'finance',
            'label' => 'Finance',
            'icon' => 'cash-stack',
            'items' => [
                [
                    'label' => 'Payments',
                    'route' => 'admin.payments.index',
                    'permission' => 'payments.view',
                    'match' => 'admin.payments.*',
                    'icon' => 'wallet2',
                ],
                [
                    'label' => 'Invoices',
                    'route' => 'admin.invoices.index',
                    'permission' => 'payments.view',
                    'match' => 'admin.invoices.*',
                    'icon' => 'receipt',
                ],
            ],
        ],
        [
            'key' => 'inventory',
            'label' => 'Inventory',
            'icon' => 'box-seam',
            'items' => [
                [
                    'label' => 'Products',
                    'route' => 'admin.products.index',
                    'permission' => 'products.view',
                    'match' => 'admin.products.index|admin.products.show|admin.products.edit|admin.products.create',
                    'icon' => 'box',
                ],
                [
                    'label' => 'Categories',
                    'route' => 'admin.categories.index',
                    'permission' => 'products.view',
                    'match' => 'admin.categories.*',
                    'icon' => 'tags',
                ],
                [
                    'label' => 'POS',
                    'route' => 'admin.pos.index',
                    'permission' => 'payments.create',
                    'match' => 'admin.pos.*',
                    'icon' => 'shop-window',
                ],
            ],
        ],
        [
            'key' => 'reports',
            'label' => 'Reports',
            'icon' => 'bar-chart-line',
            'items' => [
                [
                    'label' => 'Reports',
                    'route' => 'admin.reports.index',
                    'permission' => 'reports.view',
                    'match' => 'admin.reports.*',
                    'icon' => 'bar-chart-line',
                ],
            ],
        ],
        [
            'key' => 'settings',
            'label' => 'Settings',
            'icon' => 'gear',
            'items' => [
                [
                    'label' => 'General Settings',
                    'route' => 'admin.settings.edit',
                    'permission' => 'settings.view',
                    'match' => 'admin.settings.*',
                    'icon' => 'gear',
                ],
                [
                    'label' => 'ZKTeco Devices',
                    'route' => 'admin.zkteco-devices.index',
                    'permission' => 'zkteco-devices.view',
                    'match' => 'admin.zkteco-devices.*',
                    'icon' => 'fingerprint',
                ],
                [
                    'label' => 'Attendance Logs',
                    'route' => 'admin.attendance-logs.index',
                    'permission' => 'attendance-logs.view',
                    'match' => 'admin.attendance-logs.*',
                    'icon' => 'clock-history',
                ],
            ],
        ],
    ],

];
