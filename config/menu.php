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
            'label' => 'Gym Settings',
            'route' => 'admin.settings.edit',
            'permission' => 'settings.view',
            'match' => 'admin.settings.*',
        ],
    ],

];
