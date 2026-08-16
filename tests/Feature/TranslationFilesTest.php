<?php

/**
 * Recursively flatten translation array keys using dot notation.
 *
 * @return array<int, string>
 */
function flattenTranslationKeys(array $translations, string $prefix = ''): array
{
    $keys = [];

    foreach ($translations as $key => $value) {
        $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $keys = array_merge($keys, flattenTranslationKeys($value, $fullKey));
        } else {
            $keys[] = $fullKey;
        }
    }

    return $keys;
}

it('has matching translation keys for english and bangla locale files', function () {
    $englishFiles = glob(lang_path('en/*.php'));

    expect($englishFiles)->not->toBeEmpty();

    foreach ($englishFiles as $englishFile) {
        $filename = basename($englishFile);
        $banglaFile = lang_path("bn/{$filename}");

        expect($banglaFile)->toBeFile("Missing Bangla translation file: bn/{$filename}");

        $englishKeys = flattenTranslationKeys(require $englishFile);
        $banglaKeys = flattenTranslationKeys(require $banglaFile);

        sort($englishKeys);
        sort($banglaKeys);

        expect($banglaKeys)->toBe($englishKeys, "Translation key mismatch in {$filename}");
    }
});

it('defines supported locale labels in config', function () {
    expect(config('locale.supported'))->toBe(['en', 'bn'])
        ->and(config('locale.default'))->toBe('en')
        ->and(config('locale.labels.en'))->toBe('English')
        ->and(config('locale.labels.bn'))->toBe('বাংলা');
});
