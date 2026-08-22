<?php

namespace App\Support;

/**
 * Decides whether a ZKTeco RTLOG/ATTLOG row represents a successful door unlock.
 *
 * F22 access controllers upload denied card reads (e.g. event=27, pin=0) with the
 * same verifytype as successes. Attendance must only count verified unlocks.
 */
class ZktecoAttendanceVerifier
{
    /**
     * Access-control event codes that mean the door was unlocked / open granted.
     *
     * @var list<int>
     */
    private const UNLOCK_EVENTS = [
        0,  // Normal Verify Open
        1,  // Punch during Normal Open Time Zone
        2,  // First Card Normal Open
        3,  // Multi-Card Open / Continue Open (F22 success path)
        4,  // Emergency Password Open
        5,  // Open during Normal Open Time Zone
        8,  // Remote Opening
        14, // Fingerprint/RF verified open
        15,
        16,
        17,
        18,
        20, // Door opened correctly
        21, // Open by exit button
        22,
    ];

    /**
     * @param  array{
     *     pim?: string,
     *     verify_mode?: string,
     *     card_number?: string|null,
     *     event?: string|null
     * }  $parsed
     */
    public static function isSuccessfulUnlock(array $parsed): bool
    {
        $pim = trim((string) ($parsed['pim'] ?? ''));

        if ($pim === '' || $pim === '0') {
            return false;
        }

        if (! self::hasSuccessfulVerificationMethod($parsed)) {
            return false;
        }

        $event = $parsed['event'] ?? null;

        if ($event === null || trim((string) $event) === '') {
            return true;
        }

        return in_array((int) $event, self::UNLOCK_EVENTS, true);
    }

    /**
     * @deprecated Use isSuccessfulUnlock()
     *
     * @param  array{verify_mode: string, card_number?: string|null, pim?: string, event?: string|null}  $parsed
     */
    public static function isVerified(array $parsed): bool
    {
        return self::isSuccessfulUnlock($parsed);
    }

    /**
     * @param  array{verify_mode?: string, card_number?: string|null}  $parsed
     */
    private static function hasSuccessfulVerificationMethod(array $parsed): bool
    {
        $verifyMode = trim((string) ($parsed['verify_mode'] ?? ''));

        if ($verifyMode === '' || $verifyMode === '0') {
            return false;
        }

        if (in_array($verifyMode, ['2', '4'], true)) {
            $cardNumber = $parsed['card_number'] ?? null;

            if ($cardNumber === null) {
                return true;
            }

            $cardNumber = trim((string) $cardNumber);

            return $cardNumber !== '' && $cardNumber !== '0';
        }

        return in_array($verifyMode, ['1', '3', '15', '25'], true);
    }
}
