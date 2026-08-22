<?php

namespace App\Support;

class AppLogging
{
    /**
     * Environments where application file/console logging is allowed.
     *
     * @var list<string>
     */
    public const ENABLED_ENVIRONMENTS = ['local', 'development', 'testing'];

    public static function enabled(): bool
    {
        $forced = config('logging.enabled');

        if ($forced !== null && $forced !== '') {
            return filter_var($forced, FILTER_VALIDATE_BOOL);
        }

        return self::enabledForEnvironment(app()->environment());
    }

    public static function enabledForEnvironment(string $environment): bool
    {
        return in_array($environment, self::ENABLED_ENVIRONMENTS, true);
    }
}
