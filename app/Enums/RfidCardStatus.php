<?php

namespace App\Enums;

enum RfidCardStatus: string
{
    case Available = 'available';
    case Assigned = 'assigned';
    case Lost = 'lost';
    case Blocked = 'blocked';
    case Returned = 'returned';

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
            self::Available => 'Available',
            self::Assigned => 'Assigned',
            self::Lost => 'Lost',
            self::Blocked => 'Blocked',
            self::Returned => 'Returned',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'sg-status-badge-info',
            self::Assigned => 'sg-status-badge-active',
            self::Lost => 'sg-status-badge-inactive',
            self::Blocked => 'sg-status-badge-warning',
            self::Returned => 'sg-status-badge-muted',
        };
    }

    public function isAssignable(): bool
    {
        return $this === self::Available || $this === self::Returned;
    }

    public function preventsAssignment(): bool
    {
        return $this === self::Lost || $this === self::Blocked || $this === self::Assigned;
    }
}
