<?php

namespace App\Enums;

enum AlertType: string
{
    case MembershipExpired = 'membership_expired';
    case MembershipExpiring = 'membership_expiring';
    case LowStock = 'low_stock';
    case Birthday = 'birthday';

    public function label(): string
    {
        return match ($this) {
            self::MembershipExpired => 'Expired Membership',
            self::MembershipExpiring => 'Membership Expiring Soon',
            self::LowStock => 'Low Stock Products',
            self::Birthday => "Today's Birthdays",
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::MembershipExpired => 'danger',
            self::MembershipExpiring => 'warning',
            self::LowStock => 'warning',
            self::Birthday => 'info',
        };
    }
}
