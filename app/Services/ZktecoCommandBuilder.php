<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Builds ZKTeco ADMS command wire strings.
 *
 * User upsert syntax follows the device ADMS specification:
 * DATA USER PIN=123\tName=Example\tCard=123456\tPri=0
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

        return 'DATA DELETE user Pin='.$userId;
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
        ];

        if (! empty($user['card_number'])) {
            $fields['CardNo'] = str_pad($user['card_number'], 10, '0', STR_PAD_LEFT);
        }

        $fields['Pri'] = (string) ($user['privilege'] ?? 0);

        return 'DATA UPDATE user '.$this->formatFields($fields);
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
