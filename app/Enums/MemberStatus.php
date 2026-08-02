<?php

namespace App\Enums;

enum MemberStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Suspended => 'Suspended',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Expired => 'danger',
            self::Suspended => 'warning',
        };
    }
}
