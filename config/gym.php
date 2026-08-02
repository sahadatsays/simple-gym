<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Gym Settings
    |--------------------------------------------------------------------------
    |
    | Used when seeding the single gym record for this application.
    |
    */

    'defaults' => [
        'name' => env('GYM_NAME', 'Simple Gym'),
        'email' => env('GYM_EMAIL'),
        'phone' => env('GYM_PHONE'),
        'address' => env('GYM_ADDRESS'),
        'timezone' => env('GYM_TIMEZONE', 'UTC'),
        'currency' => env('GYM_CURRENCY', 'USD'),
        'is_open' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => 15,
    ],

];
