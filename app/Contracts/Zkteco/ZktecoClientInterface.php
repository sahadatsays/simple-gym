<?php

namespace App\Contracts\Zkteco;

interface ZktecoClientInterface
{
    public function reboot(string $serialNumber): string;

    public function restart(string $serialNumber): string;

    public function deleteUser(string $serialNumber, string $userId): string;

    /**
     * @param  array{
     *     uid?: int|null,
     *     user_id: string,
     *     name?: string|null,
     *     privilege?: int|null,
     *     card_number?: string|null
     * }  $user
     */
    public function upsertUser(string $serialNumber, array $user): string;
}
