<?php

namespace App\Enums;

enum AssetMaintenanceType: string
{
    case Preventive = 'preventive';
    case Corrective = 'corrective';
    case Inspection = 'inspection';
    case Other = 'other';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Preventive => 'Preventive',
            self::Corrective => 'Corrective',
            self::Inspection => 'Inspection',
            self::Other => 'Other',
        };
    }
}
