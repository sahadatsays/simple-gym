<?php

namespace App\Enums;

enum ZktecoDeviceStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'sg-status-badge-warning',
            self::Active => 'sg-status-badge-active',
            self::Suspended => 'sg-status-badge-inactive',
        };
    }
}
