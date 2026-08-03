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
        'currency' => env('GYM_CURRENCY', 'BDT'),
        'receipt_footer' => 'Thank you for choosing us. Stay strong!',
        'membership_reminder_days' => 7,
        'default_admission_fee' => 500,
        'enabled_payment_methods' => ['cash', 'card', 'bank', 'mobile_banking'],
        'is_open' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | ISO currency codes with display names and symbols. Amounts are shown with
    | the symbol before the value throughout the application.
    |
    */

    'currencies' => [
        'BDT' => ['name' => 'Bangladeshi Taka', 'symbol' => '৳'],
        'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
        'EUR' => ['name' => 'Euro', 'symbol' => '€'],
        'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
        'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹'],
        'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$'],
        'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$'],
        'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥'],
        'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$'],
        'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ'],
        'SAR' => ['name' => 'Saudi Riyal', 'symbol' => '﷼'],
        'MYR' => ['name' => 'Malaysian Ringgit', 'symbol' => 'RM'],
        'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => '₨'],
        'NPR' => ['name' => 'Nepalese Rupee', 'symbol' => '₨'],
        'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Categories
    |--------------------------------------------------------------------------
    */

    'product_categories' => [
        'Supplements',
        'Apparel',
        'Accessories',
        'Equipment',
        'Beverages',
        'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Local Development Login
    |--------------------------------------------------------------------------
    |
    | One-click login is only available when APP_ENV=local.
    |
    */

    'dev_login' => [
        'email' => env('DEV_LOGIN_EMAIL', 'admin@simplegym.test'),
    ],

];
