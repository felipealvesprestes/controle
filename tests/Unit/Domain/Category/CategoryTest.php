<?php

use App\Domain\Category\Category;
use App\Domain\Shared\Color;

it('changes only the given fields', function () {
    $category = new Category(7, 'Reunião', Color::Blue, true, true, 2);
    $changed = $category->with(billable: false, sortOrder: 5);

    expect($changed->id)->toBe(7)
        ->and($changed->name)->toBe('Reunião')
        ->and($changed->billable)->toBeFalse()
        ->and($changed->sortOrder)->toBe(5)
        ->and($changed->toArray())->toBe(['name' => 'Reunião', 'color' => 'blue', 'billable' => false, 'active' => true]);
});
