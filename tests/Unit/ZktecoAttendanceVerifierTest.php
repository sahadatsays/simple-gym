<?php

use App\Support\ZktecoAttendanceVerifier;

it('accepts successful unlock events from f22 rtlog', function () {
    expect(ZktecoAttendanceVerifier::isSuccessfulUnlock([
        'pim' => '4',
        'verify_mode' => '4',
        'card_number' => '7937357',
        'event' => '3',
    ]))->toBeTrue();
});

it('rejects denied access events even when verifytype looks like a card read', function () {
    expect(ZktecoAttendanceVerifier::isSuccessfulUnlock([
        'pim' => '0',
        'verify_mode' => '4',
        'card_number' => '7937357',
        'event' => '27',
    ]))->toBeFalse();
});

it('rejects unknown pin zero card reads', function () {
    expect(ZktecoAttendanceVerifier::isSuccessfulUnlock([
        'pim' => '0',
        'verify_mode' => '4',
        'card_number' => '123456',
        'event' => '0',
    ]))->toBeFalse();
});

it('rejects failed verification modes', function () {
    expect(ZktecoAttendanceVerifier::isSuccessfulUnlock([
        'pim' => '1',
        'verify_mode' => '0',
        'card_number' => '123456',
        'event' => '0',
    ]))->toBeFalse();
});

it('allows legacy attlog rows without an event field', function () {
    expect(ZktecoAttendanceVerifier::isSuccessfulUnlock([
        'pim' => '1005',
        'verify_mode' => '1',
        'card_number' => null,
        'event' => null,
    ]))->toBeTrue();
});
