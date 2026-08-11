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

    /**
     * @param  array<string, string|null>|null  $stamps
     */
    public static function cdataConfig(string $serialNumber, ?array $stamps = null): string
    {
        $stamps ??= [];

        $lines = [
            'GET OPTION FROM: '.$serialNumber,
            'ATTLOGStamp='.($stamps['ATTLOG'] ?? 'None'),
            'OPERLOGStamp='.($stamps['OPERLOG'] ?? 'None'),
            'ATTPHOTOStamp='.($stamps['ATTPHOTO'] ?? 'None'),
            'ErrorDelay=30',
            'Delay=15',
            'TransTimes=00:00;23:59',
            'TransInterval=1',
            'TransFlag=TransData AttLog OpLog AttPhoto EnrollUser ChgUser EnrollFP ChgFP UserPic',
            'Realtime=1',
            'Encrypt=0',
            'TimeZone=8',
            'ServerVer=3.1.1',
        ];

        return implode(self::LINE_ENDING, $lines).self::LINE_ENDING;
    }

    public static function ok(?string $message = null): string
    {
        return $message ?? 'OK';
    }

    public static function unavailable(string $message): string
    {
        return $message;
    }

    public static function command(int $commandId, string $command): string
    {
        return 'C:'.$commandId.':'.$command.self::LINE_ENDING;
    }
}
