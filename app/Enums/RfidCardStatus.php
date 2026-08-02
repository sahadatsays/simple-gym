<?php

namespace App\Enums;

enum RfidCardStatus: string
{
    case Unassigned = 'unassigned';
    case Active = 'active';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Unassigned => 'Unassigned',
            self::Active => 'Active',
            self::Disabled => 'Disabled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unassigned => 'sg-status-badge-inactive',
            self::Active => 'sg-status-badge-active',
            self::Disabled => 'sg-status-badge-warning',
        };
    }
}
