<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Registration = 'registration';
    case Renewal = 'renewal';
    case PosSale = 'pos_sale';

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
            self::Registration => 'Registration',
            self::Renewal => 'Renewal',
            self::PosSale => 'POS Sale',
        };
    }
}
