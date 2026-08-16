<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Active = 'active';
    case UnderMaintenance = 'under_maintenance';
    case Damaged = 'damaged';
    case Lost = 'lost';
    case Disposed = 'disposed';
    case Sold = 'sold';

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
            self::Active => 'Active',
            self::UnderMaintenance => 'Under Maintenance',
            self::Damaged => 'Damaged',
            self::Lost => 'Lost',
            self::Disposed => 'Disposed',
            self::Sold => 'Sold',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Disposed, self::Sold], true);
    }

    public function isOperational(): bool
    {
        return in_array($this, [self::Active, self::UnderMaintenance, self::Damaged], true);
    }

    /**
     * @return array<string, string>
     */
    public static function operationalOptions(): array
    {
        return collect(self::cases())
            ->filter(fn (self $status): bool => $status->isOperational())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
