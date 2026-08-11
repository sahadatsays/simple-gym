<?php

namespace App\Enums;

enum ZktecoPunchStatus: string
{
    case CheckIn = '0';
    case CheckOut = '1';
    case BreakOut = '2';
    case BreakIn = '3';
    case OvertimeIn = '4';
    case OvertimeOut = '5';

    public function label(): string
    {
        return match ($this) {
            self::CheckIn => 'Check In',
            self::CheckOut => 'Check Out',
            self::BreakOut => 'Break Out',
            self::BreakIn => 'Break In',
            self::OvertimeIn => 'Overtime In',
            self::OvertimeOut => 'Overtime Out',
        };
    }

    public static function labelFor(string $value): string
    {
        return self::tryFrom($value)?->label() ?? $value;
    }
}
