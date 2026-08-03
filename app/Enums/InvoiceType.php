<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Registration = 'registration';
    case Renewal = 'renewal';
    case PosSale = 'pos_sale';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registration',
            self::Renewal => 'Renewal',
            self::PosSale => 'POS Sale',
        };
    }
}
