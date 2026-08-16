<?php

return [
    'title' => 'ব্যবসায়িক ড্যাশবোর্ড',
    'heading' => 'ড্যাশবোর্ড',

    'quick_actions' => [
        'title' => 'দ্রুত কাজ',
        'register' => 'নিবন্ধন',
        'rfid_card' => 'আরএফআইডি কার্ড',
        'pos' => 'পিওএস',
        'orders' => 'অর্ডার',
        'payment' => 'পেমেন্ট',
        'renew' => 'নবায়ন',
    ],

    'stats' => [
        'new_registrations' => 'নতুন নিবন্ধন',
        'new_registrations_footer' => 'নির্বাচিত সময়কালে',
        'active_members' => 'সক্রিয় সদস্য',
        'active_members_footer' => 'বর্তমানে সক্রিয়',
        'expired_in_period' => 'সময়কালে মেয়াদোত্তীর্ণ',
        'expired_in_period_footer' => 'মেয়াদোত্তীর্ণ সদস্যতা',
        'period_revenue' => 'সময়কালের আয়',
        'product_sales' => 'পণ্য বিক্রয়',
        'low_stock_items' => 'কম স্টক আইটেম',
        'low_stock_items_footer' => 'পুনরায় স্টক প্রয়োজন',
    ],

    'charts' => [
        'revenue_trend' => 'আয়ের প্রবণতা',
        'registration_trend' => 'নিবন্ধনের প্রবণতা',
        'revenue_label' => 'আয়',
        'registrations_label' => 'নিবন্ধন',
    ],

    'date_filter' => [
        'title' => 'তারিখের পরিসর',
        'help' => 'নির্বাচিত সময়কালের জন্য সব KPI, চার্ট এবং টেবিল রিফ্রেশ হবে।',
        'apply_range' => 'পরিসর প্রয়োগ করুন',
        'custom' => 'কাস্টম',
    ],

    'date_presets' => [
        'today' => 'আজ',
        'yesterday' => 'গতকাল',
        'last_7_days' => 'গত ৭ দিন',
        'last_30_days' => 'গত ৩০ দিন',
        'this_month' => 'এই মাস',
        'last_month' => 'গত মাস',
        'custom_range' => 'কাস্টম পরিসর',
    ],

    'widgets' => [
        'recent_payments' => 'সাম্প্রতিক পেমেন্ট',
        'recent_payments_subtitle' => 'নির্বাচিত সময়কালের লেনদেন',
        'view_all_payments' => 'সব পেমেন্ট দেখুন',
        'recent_registrations' => 'সাম্প্রতিক নিবন্ধন',
        'recent_registrations_subtitle' => 'নির্বাচিত সময়কালের নতুন সদস্য',
        'view_all_members' => 'সব সদস্য দেখুন',
        'no_registrations' => 'এই সময়কালে কোনো নিবন্ধন পাওয়া যায়নি।',
        'low_stock_products' => 'কম স্টক পণ্য',
        'low_stock_products_subtitle' => 'মনোযোগ প্রয়োজন এমন বর্তমান ইনভেন্টরি',
        'manage_inventory' => 'ইনভেন্টরি পরিচালনা',
        'all_above_minimum' => 'সব পণ্য ন্যূনতম স্টকের উপরে আছে।',
        'upcoming_due_orders' => 'আসন্ন বকেয়া অর্ডার',
        'upcoming_due_orders_subtitle' => 'শীঘ্রই বা মেয়াদোত্তীর্ণ POS বকেয়া',
        'view_all_orders' => 'সব অর্ডার দেখুন',
        'no_upcoming_due' => 'আগামী :days দিনে কোনো বকেয়া অর্ডার নেই।',
        'no_payments' => 'এই সময়কালে কোনো পেমেন্ট পাওয়া যায়নি।',
        'no_payments_recorded' => 'এই সময়কালে কোনো পেমেন্ট রেকর্ড করা হয়নি।',
    ],

    'alerts' => [
        'title' => 'সতর্কতা',
        'subtitle' => 'আজ আপনার মনোযোগ প্রয়োজন এমন গুরুত্বপূর্ণ বিষয়।',
        'membership_expired' => 'মেয়াদোত্তীর্ণ সদস্যতা',
        'membership_expiring_soon' => 'শীঘ্রই মেয়াদ শেষ',
        'membership_expiring_in_days' => ':days দিনের মধ্যে মেয়াদ শেষ',
        'low_stock_products' => 'কম স্টক পণ্য',
        'birthdays_today' => 'আজকের জন্মদিন',
        'message_expired_one' => '১টি সদস্যতার মেয়াদ শেষ হয়েছে এবং নবায়ন প্রয়োজন।',
        'message_expired_many' => ':countটি সদস্যতার মেয়াদ শেষ হয়েছে এবং নবায়ন প্রয়োজন।',
        'message_expiring_one' => '১টি সদস্যতা আগামী :days দিনের মধ্যে মেয়াদ শেষ হবে।',
        'message_expiring_many' => ':countটি সদস্যতা আগামী :days দিনের মধ্যে মেয়াদ শেষ হবে।',
        'message_low_stock_one' => '১টি পণ্য ন্যূনতম স্টকে বা তার নিচে আছে।',
        'message_low_stock_many' => ':countটি পণ্য ন্যূনতম স্টকে বা তার নিচে আছে।',
        'message_birthday_one' => '১ জন সদস্য আজ জন্মদিন পালন করছেন।',
        'message_birthday_many' => ':count জন সদস্য আজ জন্মদিন পালন করছেন।',
        'stock_detail' => 'স্টকে :stock (ন্যূনতম :minimum)',
    ],
];
