<?php

namespace App\Support;

use App\Enums\DashboardDatePreset;
use Illuminate\Support\Carbon;

class DashboardDateRange
{
    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public DashboardDatePreset $preset,
    ) {
        $this->from = $from->copy()->startOfDay();
        $this->to = $to->copy()->endOfDay();
    }

    public static function default(): self
    {
        return self::fromPreset(DashboardDatePreset::ThisMonth);
    }

    public static function fromPreset(DashboardDatePreset $preset, ?Carbon $from = null, ?Carbon $to = null): self
    {
        return match ($preset) {
            DashboardDatePreset::Today => new self(today(), today(), $preset),
            DashboardDatePreset::Yesterday => new self(today()->subDay(), today()->subDay(), $preset),
            DashboardDatePreset::Last7Days => new self(today()->subDays(6), today(), $preset),
            DashboardDatePreset::Last30Days => new self(today()->subDays(29), today(), $preset),
            DashboardDatePreset::ThisMonth => new self(now()->startOfMonth(), now()->endOfMonth(), $preset),
            DashboardDatePreset::LastMonth => new self(
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                $preset,
            ),
            DashboardDatePreset::Custom => new self(
                ($from ?? today())->copy()->startOfDay(),
                ($to ?? today())->copy()->endOfDay(),
                $preset,
            ),
        };
    }

    /**
     * @param  array{preset?: string|null, from_date?: string|null, to_date?: string|null}  $input
     */
    public static function fromInput(array $input): self
    {
        $preset = DashboardDatePreset::tryFrom((string) ($input['preset'] ?? ''))
            ?? DashboardDatePreset::ThisMonth;

        if ($preset === DashboardDatePreset::Custom) {
            $from = filled($input['from_date'] ?? null)
                ? Carbon::parse($input['from_date'])
                : today();
            $to = filled($input['to_date'] ?? null)
                ? Carbon::parse($input['to_date'])
                : today();

            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            return self::fromPreset($preset, $from, $to);
        }

        return self::fromPreset($preset);
    }

    public function label(): string
    {
        if ($this->preset === DashboardDatePreset::Custom) {
            return $this->from->format('M j, Y').' – '.$this->to->format('M j, Y');
        }

        return $this->preset->label();
    }

    public function dayCount(): int
    {
        return (int) $this->from->copy()->startOfDay()->diffInDays($this->to->copy()->startOfDay()) + 1;
    }

    /**
     * @return array{preset: string, from_date: string, to_date: string}
     */
    public function queryParameters(): array
    {
        return [
            'preset' => $this->preset->value,
            'from_date' => $this->from->toDateString(),
            'to_date' => $this->to->toDateString(),
        ];
    }
}
