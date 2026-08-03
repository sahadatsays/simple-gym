<?php

namespace App\Enums;

use InvalidArgumentException;

enum ReportType: string
{
    case DailyCollection = 'daily-collection';
    case MonthlyCollection = 'monthly-collection';
    case Membership = 'membership';
    case ExpiredMembers = 'expired-members';
    case UpcomingExpiry = 'upcoming-expiry';
    case PosSales = 'pos-sales';
    case ProductSales = 'product-sales';
    case Stock = 'stock';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    public static function fromSlug(string $slug): self
    {
        return self::tryFrom($slug)
            ?? throw new InvalidArgumentException("Unknown report type: {$slug}");
    }

    public function label(): string
    {
        return match ($this) {
            self::DailyCollection => 'Daily Collection',
            self::MonthlyCollection => 'Monthly Collection',
            self::Membership => 'Membership Report',
            self::ExpiredMembers => 'Expired Members',
            self::UpcomingExpiry => 'Upcoming Expiry',
            self::PosSales => 'POS Sales',
            self::ProductSales => 'Product Sales',
            self::Stock => 'Stock Report',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DailyCollection => 'Day-by-day payment collections with type breakdown.',
            self::MonthlyCollection => 'Monthly revenue totals and transaction counts.',
            self::Membership => 'Member roster with plan, status, and membership dates.',
            self::ExpiredMembers => 'Members whose membership has expired.',
            self::UpcomingExpiry => 'Active members expiring within a selected window.',
            self::PosSales => 'Point-of-sale transactions and totals.',
            self::ProductSales => 'Product-level sales with revenue and profit.',
            self::Stock => 'Current inventory levels and stock value.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DailyCollection, self::MonthlyCollection => 'chart',
            self::Membership, self::ExpiredMembers, self::UpcomingExpiry => 'users',
            self::PosSales, self::ProductSales => 'shopping',
            self::Stock => 'alert',
        };
    }
}
