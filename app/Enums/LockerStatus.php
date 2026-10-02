<?php

namespace App\Enums;

enum LockerStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Maintenance = 'maintenance';
    case Disabled = 'disabled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Reserved => 'Reserved',
            self::Maintenance => 'Maintenance',
            self::Disabled => 'Disabled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'sg-status-badge-active',
            self::Reserved => 'sg-status-badge-info',
            self::Maintenance => 'sg-status-badge-warning',
            self::Disabled => 'sg-status-badge-inactive',
        };
    }

    public function canBeReserved(): bool
    {
        return $this !== self::Maintenance && $this !== self::Disabled;
    }
}
