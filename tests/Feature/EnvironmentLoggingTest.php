<?php

use App\Support\AppLogging;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

it('keeps the default log channel active while logging is enabled', function () {
    expect(AppLogging::enabled())->toBeTrue()
        ->and(config('logging.default'))->not->toBe('null');
});

it('writes nothing when the null channel is forced for production-like mode', function () {
    config(['logging.default' => 'null']);
    Log::forgetChannel('null');
    app()->forgetInstance('log');

    $path = storage_path('logs/laravel-env-logging-test.log');

    if (File::exists($path)) {
        File::delete($path);
    }

    config([
        'logging.channels.single.path' => $path,
    ]);

    Log::channel('null')->info('this should never be written to disk');

    expect(File::exists($path))->toBeFalse();
});
