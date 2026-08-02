<?php

namespace App\Support;

use Illuminate\Support\Number;

class MoneyFormatter
{
    public static function format(float|int $amount, string $currency = 'USD'): string
    {
        return Number::currency($amount, $currency);
    }
}
