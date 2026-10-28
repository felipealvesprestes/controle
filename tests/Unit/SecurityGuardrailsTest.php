<?php

use Symfony\Component\Finder\Finder;

/**
 * Lists project files whose contents match a pattern, as "relative/path:line".
 *
 * @param  list<string>  $directories
 * @param  list<string>  $names
 * @return list<string>
 */
function sourceMatches(array $directories, array $names, string $pattern): array
{
    $root = dirname(__DIR__, 2);
    $matches = [];

    foreach (Finder::create()->files()->in(array_map(fn (string $directory): string => $root.'/'.$directory, $directories))->name($names) as $file) {
        foreach (preg_split('/\R/', $file->getContents()) ?: [] as $index => $line) {
            if (preg_match($pattern, $line) === 1) {
                $matches[] = str_replace($root.'/', '', $file->getRealPath()).':'.($index + 1);
            }
        }
    }

    sort($matches);

    return $matches;
}

it('builds SQL only through bindings — SEC-23', function () {
    expect(sourceMatches(
        ['app', 'database', 'routes'],
        ['*.php'],
        '/DB::(raw|statement|unprepared)\s*\(|->\s*(where|orWhere|select|order|group|having|join)[A-Za-z]*Raw\s*\(/',
    ))->toBe([]);
});

it('escapes every Blade output except reviewed exceptions — SEC-24', function () {
    $reviewed = [
        // Server-generated QR code SVG from Fortify; holds no user input (audit run-1, unit u7).
        'resources/views/pages/settings/⚡two-factor-setup-modal.blade.php',
    ];

    $unescaped = array_map(
        fn (string $match): string => explode(':', $match)[0],
        sourceMatches(['resources/views'], ['*.blade.php'], '/\{!!/'),
    );

    expect(array_values(array_diff($unescaped, $reviewed)))->toBe([]);
});

arch('never unserializes or runs shell commands — SEC-39')
    ->expect(['unserialize', 'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen'])
    ->not->toBeUsed();
