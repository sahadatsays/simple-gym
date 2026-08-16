<?php

namespace App\Enums;

enum AssetDisposalType: string
{
    case Sale = 'sale';
    case Scrap = 'scrap';
    case Donation = 'donation';
    case WriteOff = 'write_off';
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
            self::Sale => 'Sale',
            self::Scrap => 'Scrap',
            self::Donation => 'Donation',
            self::WriteOff => 'Write Off',
            self::Other => 'Other',
        };
    }
}
