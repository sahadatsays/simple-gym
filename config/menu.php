<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Sidebar Menu
    |--------------------------------------------------------------------------
    |
    | Each item requires a permission that exists in the database. Items the
    | authenticated user cannot perform are automatically hidden.
    |
    */

    'items' => [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'permission' => 'dashboard.view',
            'match' => 'admin.dashboard',
        ],
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'permission' => 'users.view',
            'match' => 'admin.users.*',
        ],
        [
            'label' => 'Roles',
            'route' => 'admin.roles.index',
            'permission' => 'roles.view',
            'match' => 'admin.roles.*',
        ],
        [
            'label' => 'Permissions',
            'route' => 'admin.permissions.index',
            'permission' => 'permissions.view',
            'match' => 'admin.permissions.*',
        ],
        [
            'label' => 'Membership Plans',
            'route' => 'admin.membership-plans.index',
            'permission' => 'membership-plans.view',
            'match' => 'admin.membership-plans.*',
        ],
        [
            'label' => 'Members',
            'route' => 'admin.members.index',
            'permission' => 'members.view',
            'match' => 'admin.members.*',
        ],
        [
            'label' => 'POS',
            'route' => 'admin.pos.index',
            'permission' => 'payments.create',
            'match' => 'admin.pos.*',
        ],
        [
            'label' => 'Payments',
            'route' => 'admin.payments.index',
            'permission' => 'payments.view',
            'match' => 'admin.payments.*',
        ],
        [
            'label' => 'Products',
            'route' => 'admin.products.index',
            'permission' => 'products.view',
            'match' => 'admin.products.*',
        ],
        [
            'label' => 'RFID Cards',
            'route' => 'admin.rfid-cards.index',
            'permission' => 'rfid-cards.view',
            'match' => 'admin.rfid-cards.*',
        ],
        [
            'label' => 'Reports',
            'route' => 'admin.reports.index',
            'permission' => 'reports.view',
            'match' => 'admin.reports.*',
        ],
        [
            'label' => 'Gym Settings',
            'route' => 'admin.settings.edit',
            'permission' => 'settings.view',
            'match' => 'admin.settings.*',
        ],
    ],

];
