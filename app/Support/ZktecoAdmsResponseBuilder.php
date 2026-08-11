<?php

namespace App\Support;

class ZktecoAdmsResponseBuilder
{
    private const string LINE_ENDING = "\r\n";

    /**
     * @param  array<string, string|int>  $lines
     */
    public static function lines(array $lines): string
    {
        $content = '';

        foreach ($lines as $key => $value) {
            $content .= $key.'='.$value.self::LINE_ENDING;
        }

        return $content;
    }

    public static function registry(): string
    {
        return self::lines([
            'RegistryCode' => 0,
            'ServerVersion' => '3.1.1',
            'ServerName' => 'ADMS_Laravel',
            'PushVersion' => '3.0',
            'RefreshDelay' => 15,
            'Delay' => 15,
            'TransTimes' => '00:00;23:59',
            'TransInterval' => 1,
            'TransFlag' => '1111111111',
            'Realtime' => 1,
            'Encrypt' => 0,
        ]);
    }

    public static function push(): string
    {
        return self::lines([
            'ServerVersion' => '3.1.1',
            'ServerName' => 'ADMS_Laravel',
            'PushVersion' => '3.0',
            'RegistryCode' => 0,
            'RefreshDelay' => 15,
        ]);
    }

    public static function command(int $commandId, string $command): string
    {
        return 'C:'.$commandId.':'.$command.self::LINE_ENDING;
    }
}
