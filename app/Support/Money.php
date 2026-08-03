<?php

namespace App\Support;

class Money
{
    public static function round(float $amount, int $precision = 2): float
    {
        return round($amount, $precision);
    }

    public static function greaterThan(float $left, float $right, int $precision = 2): bool
    {
        return self::round($left, $precision) > self::round($right, $precision);
    }

    public static function lessThan(float $left, float $right, int $precision = 2): bool
    {
        return self::round($left, $precision) < self::round($right, $precision);
    }
}
