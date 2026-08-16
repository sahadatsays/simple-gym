<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Active = 'active';
    case UnderMaintenance = 'under_maintenance';
    case Disposed = 'disposed';
    case Inactive = 'inactive';

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
            self::Disposed => 'Disposed',
            self::Inactive => 'Inactive',
        };
    }
}
