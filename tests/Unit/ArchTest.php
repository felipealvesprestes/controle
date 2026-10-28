<?php

arch('the domain does not depend on the framework or outer layers — ADR-0002')
    ->expect('App\Domain')
    ->not->toUse([
        'Illuminate',
        'Laravel',
        'Livewire',
        'Flux',
        'App\Models',
        'App\Http',
        'App\Livewire',
        'App\Application',
        'App\Infrastructure',
    ]);

arch('application use cases do not depend on infrastructure or the interface — ADR-0002')
    ->expect('App\Application')
    ->not->toUse([
        'App\Infrastructure',
        'App\Http',
        'App\Livewire',
        'App\Models',
    ]);

arch('domain classes are final — ADR-0002')
    ->expect('App\Domain')
    ->classes()
    ->toBeFinal();

arch('shared value objects are immutable — ADR-0004')
    ->expect('App\Domain\Shared')
    ->classes()
    ->toBeReadonly();

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();
