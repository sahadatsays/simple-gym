<?php

namespace App\Support;

use Illuminate\Support\Number;

class MoneyFormatter
{
    public static function symbol(string $currency): string
    {
        return CurrencyRegistry::symbol($currency);
    }

    public static function format(float|int|string $amount, string $currency = 'USD'): string
    {
        $symbol = self::symbol($currency);
        $formatted = Number::format((float) $amount, maxPrecision: 2);

        return $symbol.$formatted;
    }
}
