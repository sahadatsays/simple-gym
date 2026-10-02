<?php

namespace App\Enums;

enum BorrowingStatus: string
{
    case Active = 'active';
    case PartiallyRepaid = 'partially_repaid';
    case FullyRepaid = 'fully_repaid';
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
            self::Active => 'Active',
            self::PartiallyRepaid => 'Partially Repaid',
            self::FullyRepaid => 'Fully Repaid',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active, self::FullyRepaid => 'sg-status-badge-active',
            self::PartiallyRepaid => 'sg-status-badge-warning',
            self::Cancelled => 'sg-status-badge-inactive',
        };
    }
}
