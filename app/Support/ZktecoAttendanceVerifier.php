<?php

namespace App\Support;

class ZktecoAttendanceVerifier
{
    /**
     * @param  array{verify_mode: string, card_number?: string|null}  $parsed
     */
    public static function isVerified(array $parsed): bool
    {
        $verifyMode = trim($parsed['verify_mode'] ?? '');

        if ($verifyMode === '' || $verifyMode === '0') {
            return false;
        }

        if (in_array($verifyMode, ['2', '4'], true)) {
            $cardNumber = $parsed['card_number'] ?? null;

            if ($cardNumber === null) {
                return true;
            }

            $cardNumber = trim($cardNumber);

            return $cardNumber !== '' && $cardNumber !== '0';
        }

        return in_array($verifyMode, ['1', '3', '15'], true);
    }
}
