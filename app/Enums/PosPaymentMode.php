<?php

namespace App\Enums;

enum PosPaymentMode: string
{
    case Full = 'full';
    case Partial = 'partial';
    case Due = 'due';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $mode): array => [$mode->value => $mode->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Pay in full',
            self::Partial => 'Partial payment',
            self::Due => 'Pay later (due)',
        };
    }

    public static function fromAmount(float $amountPaid, float $totalDue): self
    {
        if ($amountPaid <= 0) {
            return self::Due;
        }

        if ($amountPaid >= $totalDue) {
            return self::Full;
        }

        return self::Partial;
    }
}
