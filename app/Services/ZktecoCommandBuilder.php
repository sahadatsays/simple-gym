<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Builds ZKTeco ADMS command wire strings.
 *
 * Device mapping:
 * - Pin: rfid_cards.id (PIM)
 * - CardNo: rfid_cards.card_number
 *
 * F22 access control requires timezone → group → unlockcomb before user sync,
 * otherwise the device returns "Verify Failed: Pause the Time Period".
 */
class ZktecoCommandBuilder
{
    private const ALL_DAY_SEGMENT = '00002359';

    /**
     * @var list<string>
     */
    private const WEEK_DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    public function reboot(): string
    {
        return 'REBOOT';
    }

    public function clearLog(): string
    {
        return 'CLEAR LOG';
    }

    public function clearUsers(): string
    {
        return 'CLEAR DATA';
    }

    public function deleteUser(string $pim): string
    {
        $this->assertPim($pim);

        return 'DATA DELETE user Pin='.$pim;
    }

    /**
     * Factory timezone #1: every day 00:00–23:59 (F22 "Pause the Time Period" fix).
     */
    public function upsertTimezone(int $timezoneNumber = 1): string
    {
        $fields = [
            'TNo' => (string) $timezoneNumber,
        ];

        foreach (self::WEEK_DAYS as $day) {
            $fields[$day.'Time1'] = self::ALL_DAY_SEGMENT;
            $fields[$day.'Time2'] = '0';
            $fields[$day.'Time3'] = '0';
        }

        return 'DATA UPDATE timezone '.$this->formatFields($fields);
    }

    /**
     * Group #1 bound to timezone #1 and marked valid for door access.
     *
     * Newer firmware rejects "DATA UPDATE group"; use SET DATA GROUP.
     */
    public function upsertGroup(int $groupNumber = 1, int $timezoneNumber = 1): string
    {
        return 'SET DATA GROUP GNo='.$groupNumber."\tName=Full_Access\tValid=1\tGTimezone=".$timezoneNumber;
    }

    /**
     * Unlock combination linking group #1 to the physical door relay.
     *
     * Newer firmware rejects "DATA UPDATE unlockcomb"; use SET DATA UNLOCKCOMB.
     */
    public function upsertUnlockCombination(int $combinationNumber = 1, int $groupNumber = 1): string
    {
        return 'SET DATA UNLOCKCOMB CombNo='.$combinationNumber."\tGNo1=".$groupNumber;
    }

    /**
     * @param  array{
     *     pim: string|int,
     *     name?: string|null,
     *     card_number?: string|null,
     *     privilege?: int|null,
     *     group?: int|null,
     *     timezone?: int|null
     * }  $user
     */
    public function upsertUser(array $user): string
    {
        $this->assertPim($user['pim'] ?? '');

        $fields = [
            'Pin' => (string) $user['pim'],
            'Name' => $user['name'] ?? '',
        ];

        if (! empty($user['card_number'])) {
            $fields['CardNo'] = (string) $user['card_number'];
        }

        $fields['Pri'] = (string) ($user['privilege'] ?? 0);
        $fields['Grp'] = (string) ($user['group'] ?? 1);
        $fields['Timezone'] = (string) ($user['timezone'] ?? 1);

        return 'DATA UPDATE user '.$this->formatFields($fields);
    }

    /**
     * Sequential F22 factory-access reset packet:
     * timezone → group → unlockcomb → user (Grp + TZ).
     *
     * @param  array{
     *     pim: string|int,
     *     name?: string|null,
     *     card_number?: string|null,
     *     privilege?: int|null,
     *     group?: int|null,
     *     timezone?: int|null
     * }  $user
     * @return list<string>
     */
    public function factoryAccessResetPacket(array $user): array
    {
        $group = (int) ($user['group'] ?? 1);
        $timezone = (int) ($user['timezone'] ?? 1);

        return [
            $this->upsertTimezone($timezone),
            $this->upsertGroup($group, $timezone),
            $this->upsertUnlockCombination(1, $group),
            $this->upsertUser($user),
        ];
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function formatFields(array $fields): string
    {
        $segments = [];

        foreach ($fields as $key => $value) {
            $segments[] = $key.'='.trim($value);
        }

        return implode("\t", $segments);
    }

    private function assertPim(string $pim): void
    {
        if (trim($pim) === '') {
            throw new InvalidArgumentException('An RFID card PIM is required.');
        }
    }
}
