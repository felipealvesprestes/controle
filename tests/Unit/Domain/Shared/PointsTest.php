<?php

use App\Domain\Shared\Minutes;
use App\Domain\Shared\Points;

it('is worth fifteen minutes per point — RN-01', function () {
    expect(Points::MINUTES_PER_POINT)->toBe(15)
        ->and(Points::of(4)->toMinutes()->value)->toBe(60);
});

it('rejects negative points', function () {
    Points::of(-1);
})->throws(InvalidArgumentException::class, 'Points cannot be negative, got -1.');

it('covers minutes with the smallest whole number of points — RN-01', function (int $minutes, int $points) {
    expect(Points::coveringMinutes(Minutes::of($minutes))->value)->toBe($points);
})->with([
    [0, 0],
    [1, 1],
    [15, 1],
    [16, 2],
    [652, 44],
    [1564, 105],
    [1324, 89],
]);

it('converts minutes to exact points only when they are whole points', function () {
    expect(Points::exactlyFrom(Minutes::of(45))?->value)->toBe(3)
        ->and(Points::exactlyFrom(Minutes::of(46)))->toBeNull();
});
