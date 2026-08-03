<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Registration = 'registration';
    case Renewal = 'renewal';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registration',
            self::Renewal => 'Renewal',
        };
    }
}
