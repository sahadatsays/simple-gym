<?php

namespace App\Services\Zkteco;

use App\Contracts\Zkteco\ZktecoClientInterface;
use InvalidArgumentException;

class AdmsZktecoClient implements ZktecoClientInterface
{
    public function reboot(string $serialNumber): string
    {
        $this->assertSerialNumber($serialNumber);

        return 'REBOOT';
    }

    public function restart(string $serialNumber): string
    {
        $this->assertSerialNumber($serialNumber);

        return 'RESTART';
    }

    public function deleteUser(string $serialNumber, string $userId): string
    {
        $this->assertSerialNumber($serialNumber);
        $this->assertUserId($userId);

        return 'DATA DELETE USERINFO PIN='.$userId;
    }

    public function upsertUser(string $serialNumber, array $user): string
    {
        $this->assertSerialNumber($serialNumber);
        $this->assertUserId($user['user_id'] ?? '');

        $fields = [
            'PIN' => $user['user_id'],
            'Name' => $user['name'] ?? '',
            'Pri' => (string) ($user['privilege'] ?? 0),
        ];

        if (array_key_exists('uid', $user) && $user['uid'] !== null) {
            $fields = ['UID' => (string) $user['uid']] + $fields;
        }

        if (! empty($user['card_number'])) {
            $fields['Card'] = $user['card_number'];
        }

        return 'DATA UPDATE USERINFO '.$this->formatFields($fields);
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

    private function assertSerialNumber(string $serialNumber): void
    {
        if (trim($serialNumber) === '') {
            throw new InvalidArgumentException('A device serial number is required.');
        }
    }

    private function assertUserId(string $userId): void
    {
        if (trim($userId) === '') {
            throw new InvalidArgumentException('A user ID is required.');
        }
    }
}
