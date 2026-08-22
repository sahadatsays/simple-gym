<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Builds ZKTeco ADMS command wire strings.
 *
 * Device mapping:
 * - Pin: rfid_cards.id (PIM)
 * - CardID: rfid_cards.card_number
 */
class ZktecoCommandBuilder
{
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
     * @param  array{
     *     pim: string|int,
     *     name?: string|null,
     *     card_number?: string|null,
     *     privilege?: int|null,
     *     group?: int|null
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

        return 'DATA UPDATE user '.$this->formatFields($fields);
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
