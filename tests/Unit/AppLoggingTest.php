<?php

use App\Support\AppLogging;

it('enables logging for local development and testing environments', function (string $environment) {
    expect(AppLogging::enabledForEnvironment($environment))->toBeTrue();
})->with(['local', 'development', 'testing']);

it('disables logging for production and staging environments', function (string $environment) {
    expect(AppLogging::enabledForEnvironment($environment))->toBeFalse();
})->with(['production', 'staging', 'prod']);

it('respects an explicit LOG_ENABLED override', function () {
    config(['logging.enabled' => true]);
    expect(AppLogging::enabled())->toBeTrue();

    config(['logging.enabled' => false]);
    expect(AppLogging::enabled())->toBeFalse();
});
