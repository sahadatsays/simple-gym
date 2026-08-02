<?php

namespace App\Support;

class Flash
{
    public const SUCCESS = 'success';

    public const ERROR = 'danger';

    public const WARNING = 'warning';

    public const INFO = 'info';

    public static function success(string $message): void
    {
        session()->flash('flash.type', self::SUCCESS);
        session()->flash('flash.message', $message);
    }

    public static function error(string $message): void
    {
        session()->flash('flash.type', self::ERROR);
        session()->flash('flash.message', $message);
    }

    public static function warning(string $message): void
    {
        session()->flash('flash.type', self::WARNING);
        session()->flash('flash.message', $message);
    }

    public static function info(string $message): void
    {
        session()->flash('flash.type', self::INFO);
        session()->flash('flash.message', $message);
    }
}
