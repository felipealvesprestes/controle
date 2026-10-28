<?php

use Symfony\Component\Finder\Finder;

it('translates every interface string into pt_BR', function () {
    $root = dirname(__DIR__, 2);
    $translations = json_decode((string) file_get_contents($root.'/lang/pt_BR.json'), true, flags: JSON_THROW_ON_ERROR);
    $missing = [];

    foreach (Finder::create()->files()->in([$root.'/resources/views', $root.'/app'])->name('*.php') as $file) {
        preg_match_all('/__\(\s*([\'"])(.+?)(?<!\\\\)\1/', $file->getContents(), $matches);

        foreach ($matches[2] as $key) {
            $isGroupKey = preg_match('/^[a-z_]+\.[a-z_.]*$/', $key) === 1;

            if (! $isGroupKey && ! array_key_exists(stripslashes($key), $translations)) {
                $missing[] = $key;
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});
