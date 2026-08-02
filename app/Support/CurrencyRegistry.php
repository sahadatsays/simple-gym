<?php

namespace App\Support;

class CurrencyRegistry
{
    /**
     * @return array<string, array{name: string, symbol: string}>
     */
    public static function all(): array
    {
        /** @var array<string, array{name: string, symbol: string}> $currencies */
        $currencies = config('gym.currencies');

        return $currencies;
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function symbol(string $currency): string
    {
        $code = strtoupper($currency);

        return self::all()[$code]['symbol'] ?? $code;
    }

    public static function name(string $currency): string
    {
        $code = strtoupper($currency);

        return self::all()[$code]['name'] ?? $code;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::all())
            ->mapWithKeys(fn (array $currency, string $code): array => [
                $code => sprintf('%s %s (%s)', $currency['symbol'], $currency['name'], $code),
            ])
            ->all();
    }
}
