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
            self::MembershipExpired => __('dashboard.alerts.membership_expired'),
            self::MembershipExpiring => __('dashboard.alerts.membership_expiring_soon'),
            self::LowStock => __('dashboard.alerts.low_stock_products'),
            self::Birthday => __('dashboard.alerts.birthdays_today'),
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
