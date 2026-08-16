<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }
}
