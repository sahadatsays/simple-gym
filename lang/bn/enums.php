<?php

return [
    'member_status' => [
        'pending' => 'অপেক্ষমাণ',
        'active' => 'সক্রিয়',
        'expired' => 'মেয়াদোত্তীর্ণ',
        'suspended' => 'স্থগিত',
    ],

    'gender' => [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
    ],

    'invoice_status' => [
        'unpaid' => 'অপরিশোধিত',
        'partial' => 'আংশিক পরিশোধ',
        'paid' => 'পরিশোধিত',
        'void' => 'বাতিল',
    ],

    'invoice_type' => [
        'registration' => 'Registration',
        'renewal' => 'Renewal',
        'pos_sale' => 'POS Sale',
    ],

    'payment_method' => [
        'cash' => 'Cash',
        'card' => 'Card',
        'bank' => 'Bank',
        'mobile_banking' => 'Mobile Banking',
    ],

    'payment_type' => [
        'admission_fee' => 'ভর্তি ফি',
        'membership_fee' => 'সদস্যতা ফি',
        'pos_sale' => 'POS বিক্রয়',
    ],

    'payment_status' => [
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    'product_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'plan_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'rfid_card_status' => [
        'unassigned' => 'Unassigned',
        'active' => 'Active',
        'disabled' => 'Disabled',
    ],

    'zkteco_device_status' => [
        'pending' => 'Pending',
        'active' => 'Active',
        'suspended' => 'Suspended',
    ],

    'zkteco_punch_status' => [
        'check_in' => 'Check In',
        'check_out' => 'Check Out',
        'break_out' => 'Break Out',
        'break_in' => 'Break In',
        'overtime_in' => 'Overtime In',
        'overtime_out' => 'Overtime Out',
    ],

    'zkteco_verify_mode' => [
        'password' => 'Password',
        'fingerprint' => 'Fingerprint',
        'card' => 'Card',
        'password_fingerprint' => 'Password + Fingerprint',
        'card_scan' => 'Card Scan',
        'face' => 'Face',
    ],

    'pos_payment_mode' => [
        'full' => 'Pay in full',
        'partial' => 'Partial payment',
        'due' => 'Pay later (due)',
    ],

    'member_access_restriction_group' => [
        'male' => 'Male members',
    ],

    'alert_type' => [
        'membership_expired' => 'Expired Membership',
        'membership_expiring' => 'Membership Expiring Soon',
        'low_stock' => 'Low Stock Products',
        'birthday' => "Today's Birthdays",
    ],

    'dashboard_date_preset' => [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'custom_range' => 'Custom Range',
    ],
];
