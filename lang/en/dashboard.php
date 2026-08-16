<?php

return [
    'title' => 'Business Dashboard',
    'heading' => 'Dashboard',

    'quick_actions' => [
        'title' => 'Quick Actions',
        'register' => 'Register',
        'rfid_card' => 'RFID Card',
        'pos' => 'POS',
        'orders' => 'Orders',
        'payment' => 'Payment',
        'renew' => 'Renew',
    ],

    'stats' => [
        'new_registrations' => 'New Registrations',
        'new_registrations_footer' => 'In selected period',
        'active_members' => 'Active Members',
        'active_members_footer' => 'Currently active',
        'expired_in_period' => 'Expired in Period',
        'expired_in_period_footer' => 'Memberships expired',
        'period_revenue' => 'Period Revenue',
        'product_sales' => 'Product Sales',
        'low_stock_items' => 'Low Stock Items',
        'low_stock_items_footer' => 'Needs restocking',
    ],

    'charts' => [
        'revenue_trend' => 'Revenue Trend',
        'registration_trend' => 'Registration Trend',
        'revenue_label' => 'Revenue',
        'registrations_label' => 'Registrations',
    ],

    'date_filter' => [
        'title' => 'Date Range',
        'help' => 'All KPIs, charts, and tables refresh for the selected period.',
        'apply_range' => 'Apply Range',
        'custom' => 'Custom',
    ],

    'date_presets' => [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'custom_range' => 'Custom Range',
    ],

    'widgets' => [
        'recent_payments' => 'Recent Payments',
        'recent_payments_subtitle' => 'Transactions in the selected period',
        'view_all_payments' => 'View all payments',
        'recent_registrations' => 'Recent Registrations',
        'recent_registrations_subtitle' => 'New members in the selected period',
        'view_all_members' => 'View all members',
        'no_registrations' => 'No registrations found for this period.',
        'low_stock_products' => 'Low Stock Products',
        'low_stock_products_subtitle' => 'Current inventory needing attention',
        'manage_inventory' => 'Manage inventory',
        'all_above_minimum' => 'All products are above minimum stock levels.',
        'upcoming_due_orders' => 'Upcoming Due Orders',
        'upcoming_due_orders_subtitle' => 'Open POS balances due soon or overdue',
        'view_all_orders' => 'View all orders',
        'no_upcoming_due' => 'No upcoming due orders in the next :days days.',
        'no_payments' => 'No payments found for this period.',
    ],

    'alerts' => [
        'title' => 'Alerts',
        'subtitle' => 'Important items that need your attention today.',
        'membership_expired' => 'Expired Membership',
        'membership_expiring_soon' => 'Membership Expiring Soon',
        'membership_expiring_in_days' => 'Membership Expiring in :days Days',
        'low_stock_products' => 'Low Stock Products',
        'birthdays_today' => "Today's Birthdays",
        'message_expired_one' => '1 membership has expired and needs renewal.',
        'message_expired_many' => ':count memberships have expired and need renewal.',
        'message_expiring_one' => '1 membership will expire within the next :days days.',
        'message_expiring_many' => ':count memberships will expire within the next :days days.',
        'message_low_stock_one' => '1 product is at or below minimum stock.',
        'message_low_stock_many' => ':count products are at or below minimum stock.',
        'message_birthday_one' => '1 member is celebrating a birthday today.',
        'message_birthday_many' => ':count members are celebrating birthdays today.',
        'stock_detail' => ':stock in stock (min :minimum)',
    ],
];
