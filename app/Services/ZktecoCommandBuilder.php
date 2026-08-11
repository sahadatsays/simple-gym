<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Builds ZKTeco ADMS command wire strings.
 *
 * Command syntax matches the existing standalone ADMS client and common Push SDK
 * formats. Verify against your physical device firmware if a command is rejected.
 */
class ZktecoCommandBuilder
{
    public function reboot(): string
    {
        return 'REBOOT';
    }

    public function restart(): string
    {
        return 'RESTART';
    }

    public function deleteUser(string $userId): string
    {
        $this->assertUserId($userId);

        return 'DATA DELETE User Pin='.$userId;
    }

    /**
     * @param  array{
     *     uid?: int|null,
     *     user_id: string,
     *     name?: string|null,
     *     privilege?: int|null,
     *     card_number?: string|null
     * }  $user
     */
    public function upsertUser(array $user): string
    {
        $this->assertUserId($user['user_id'] ?? '');

        $fields = [
            'Pin' => $user['user_id'],
            'Name' => $user['name'] ?? '',
            'Pri' => (string) ($user['privilege'] ?? 0),
        ];

        if (array_key_exists('uid', $user) && $user['uid'] !== null) {
            $fields = ['UID' => (string) $user['uid']] + $fields;
        }

        if (! empty($user['card_number'])) {
            $fields['Card'] = $user['card_number'];
        }

        return 'DATA User '.$this->formatFields($fields);
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function formatFields(array $fields): string
    {
        $segments = [];

        foreach ($fields as $key => $value) {
            $segments[] = $key.'='.$value;
        }

        return implode("\t", $segments);
    }

    private function assertUserId(string $userId): void
    {
        if (trim($userId) === '') {
            throw new InvalidArgumentException('A user ID is required.');
        }
    }
}
