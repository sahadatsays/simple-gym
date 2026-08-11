<?php

namespace App\Enums;

enum ZktecoVerifyMode: string
{
    case Password = '0';
    case Fingerprint = '1';
    case Card = '2';
    case PasswordFingerprint = '3';
    case CardScan = '4';
    case Face = '15';

    public function label(): string
    {
        return match ($this) {
            self::Password => 'Password',
            self::Fingerprint => 'Fingerprint',
            self::Card => 'Card',
            self::PasswordFingerprint => 'Password + Fingerprint',
            self::CardScan => 'Card Scan',
            self::Face => 'Face',
        };
    }

    public static function labelFor(string $value): string
    {
        return self::tryFrom($value)?->label() ?? $value;
    }
}
