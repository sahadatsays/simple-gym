<?php

use App\Support\MoneyFormatter;

it('returns the currency symbol for known codes', function () {
    expect(MoneyFormatter::symbol('BDT'))->toBe('৳')
        ->and(MoneyFormatter::symbol('USD'))->toBe('$');
});

it('formats amounts with the symbol before the value', function () {
    $formatted = MoneyFormatter::format(1500, 'BDT');

    expect($formatted)->toStartWith('৳')
        ->and($formatted)->toContain('1,500');
});

it('falls back to the currency code when no symbol is mapped', function () {
    expect(MoneyFormatter::symbol('XYZ'))->toBe('XYZ');
});
