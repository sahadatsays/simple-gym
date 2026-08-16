<?php

return [
    'registration' => [
        'plan_required' => 'Please select a membership plan.',
        'amount_insufficient' => 'Payment amount must cover the full invoice total.',
    ],

    'attributes' => [
        'enabled_payment_methods' => 'payment methods',
        'membership_reminder_days' => 'membership reminder days',
        'default_admission_fee' => 'default admission fee',
        'receipt_footer' => 'receipt footer',
    ],

    'pos' => [
        'discount_exceeds_subtotal' => 'Discount cannot exceed the cart subtotal.',
        'pay_exceeds_total' => 'Pay amount cannot exceed the billing total.',
        'member_required' => 'A member is required when the pay amount is less than the billing total.',
        'product_missing' => 'One or more products in the cart no longer exist.',
        'product_unavailable' => ':name is not available for sale.',
        'insufficient_stock' => 'Insufficient stock for :name. Available: :stock.',
    ],

    'payment' => [
        'insufficient_amount' => 'Payment failed: received :received, but :required is required.',
        'exceeds_invoice' => 'Payment failed: received :received, but invoice total is only :total.',
        'declined' => 'Payment was declined and could not be processed.',
        'already_paid' => 'This invoice has already been paid.',
    ],

    'invoice' => [
        'discount_on_paid' => 'Cannot apply a discount to a paid invoice.',
        'discount_negative' => 'Discount amount cannot be negative.',
        'discount_exceeds_subtotal' => 'Discount cannot exceed the invoice subtotal.',
    ],

    'order' => [
        'not_pos' => 'Only POS orders can be deleted.',
        'not_same_day' => 'Orders can only be deleted on the same day they were placed.',
        'product_missing' => 'Cannot delete this order because :name no longer exists.',
    ],

    'rfid' => [
        'only_unassigned' => 'Only unassigned cards can be assigned to a member.',
        'not_unassigned' => 'This card cannot be assigned because it is not unassigned.',
        'already_disabled' => 'This card is already disabled.',
        'only_disabled' => 'Only disabled cards can be enabled.',
        'not_assigned' => 'This card is not assigned to a member.',
        'member_expired' => 'Cannot enable card for an expired member. Renew membership first.',
        'member_has_active_card' => 'This member already has an active RFID card.',
        'card_not_found' => 'The selected RFID card was not found.',
        'card_not_assigned' => 'The selected RFID card is not assigned to a member.',
        'restriction_active' => 'This member cannot be synced to devices while access restrictions are active.',
        'pim_required' => 'An RFID card PIM is required.',
    ],

    'member' => [
        'phone_exists' => 'A member with this phone number is already registered.',
        'email_exists' => 'A member with this email is already registered.',
        'plan_unavailable' => 'Selected membership plan is not available.',
        'rfid_unavailable' => 'Selected RFID card is not available for assignment.',
        'pending_must_register' => 'Pending members must complete registration before renewal.',
    ],

    'category' => [
        'has_products' => 'Cannot delete a category that has products assigned. Reassign or remove those products first.',
    ],

    'membership_plan' => [
        'has_members' => 'This plan cannot be deleted because it is assigned to members.',
    ],

    'product' => [
        'stock_below_zero' => 'Stock cannot be reduced below zero.',
    ],

    'role' => [
        'protected' => 'This role is protected and cannot be deleted.',
    ],

    'permission' => [
        'system_create' => 'System default permissions cannot be created manually. Run the permission seeder to sync defaults.',
        'system_update' => 'System default permissions cannot be updated.',
        'reserved_name' => 'A permission name reserved for system defaults cannot be used.',
        'system_delete' => 'System default permissions cannot be deleted.',
    ],

    'upload' => [
        'unsupported_image' => 'Unsupported image type.',
        'logo_read_failed' => 'Unable to read uploaded logo.',
        'logo_prepare_failed' => 'Unable to prepare the uploaded logo.',
        'logo_optimize_failed' => 'Unable to optimize the uploaded logo.',
        'photo_read_failed' => 'Unable to read uploaded photo.',
        'photo_prepare_failed' => 'Unable to prepare the uploaded photo.',
        'photo_optimize_failed' => 'Unable to optimize the uploaded photo.',
    ],
];
