<?php

namespace App\Enums;

enum DashboardDatePreset: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Today',
            self::Yesterday => 'Yesterday',
            self::Last7Days => 'Last 7 Days',
            self::Last30Days => 'Last 30 Days',
            self::ThisMonth => 'This Month',
            self::LastMonth => 'Last Month',
            self::Custom => 'Custom Range',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->reject(fn (self $preset): bool => $preset === self::Custom)
            ->mapWithKeys(fn (self $preset): array => [$preset->value => $preset->label()])
            ->all();
    }
}
