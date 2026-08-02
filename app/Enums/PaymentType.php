<?php

namespace App\Enums;

enum PaymentType: string
{
    case Membership = 'membership';
    case Product = 'product';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Membership => 'Membership',
            self::Product => 'Product Sale',
            self::Other => 'Other',
        };
    }
}
