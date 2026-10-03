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
    case Investments = 'investments';
    case Assets = 'assets';
    case AssetCategories = 'asset-categories';
    case AssetMaintenance = 'asset-maintenance';
    case AssetValueSummary = 'asset-value-summary';
    case Expenses = 'expenses';
    case Borrowings = 'borrowings';
    case RfidCards = 'rfid-cards';
    case Lockers = 'lockers';
    case FinancialSummary = 'financial-summary';

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
            self::Investments => 'Investment Report',
            self::Assets => 'Asset Report',
            self::AssetCategories => 'Asset Category Report',
            self::AssetMaintenance => 'Maintenance Report',
            self::AssetValueSummary => 'Asset Value Summary',
            self::Expenses => 'Expense Report',
            self::Borrowings => 'Borrowing Report',
            self::RfidCards => 'RFID Card Report',
            self::Lockers => 'Locker Report',
            self::FinancialSummary => 'Financial Summary',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DailyCollection => 'Day-by-day payment collections with type breakdown.',
            self::MonthlyCollection => 'Monthly revenue totals and transaction counts.',
            self::Membership => 'Member roster with plan, status, membership dates, and outstanding due.',
            self::ExpiredMembers => 'Members whose membership has expired.',
            self::UpcomingExpiry => 'Active members expiring within a selected window.',
            self::PosSales => 'Point-of-sale transactions and totals.',
            self::ProductSales => 'Product-level sales with revenue and profit.',
            self::Stock => 'Current inventory levels and stock value.',
            self::Investments => 'Owner investments with category, amount, and payment details.',
            self::Assets => 'Asset register with purchase, value, condition, and status.',
            self::AssetCategories => 'Asset counts and values grouped by category.',
            self::AssetMaintenance => 'Maintenance history with costs and service schedules.',
            self::AssetValueSummary => 'Purchase value, current asset value, and maintenance spend.',
            self::Expenses => 'Expense transactions with category totals and payment details.',
            self::Borrowings => 'Borrowed money with repayments, remaining balance, and due dates.',
            self::RfidCards => 'Card assignments with member, issue date, and status.',
            self::Lockers => 'Locker reservations with member, dates, and status.',
            self::FinancialSummary => 'Operating revenue, expenses, net result, owner investment, and outstanding due.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DailyCollection, self::MonthlyCollection, self::AssetValueSummary, self::FinancialSummary => 'chart',
            self::Membership, self::ExpiredMembers, self::UpcomingExpiry => 'users',
            self::PosSales, self::ProductSales, self::Assets, self::AssetCategories => 'shopping',
            self::Stock, self::AssetMaintenance => 'alert',
            self::Investments, self::Expenses, self::Borrowings => 'wallet',
            self::RfidCards => 'users',
            self::Lockers => 'shopping',
        };
    }

    public function isAssetInvestmentReport(): bool
    {
        return in_array($this, [
            self::Investments,
            self::Assets,
            self::AssetCategories,
            self::AssetMaintenance,
            self::AssetValueSummary,
        ], true);
    }

    public function isExpenseReport(): bool
    {
        return $this === self::Expenses;
    }

    public function isBorrowingReport(): bool
    {
        return $this === self::Borrowings;
    }

    public function isRfidCardReport(): bool
    {
        return $this === self::RfidCards;
    }

    public function isLockerReport(): bool
    {
        return $this === self::Lockers;
    }
}
