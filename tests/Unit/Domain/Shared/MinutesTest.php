<?php

use App\Domain\Shared\Minutes;

it('keeps a non-negative whole number of minutes — RN-07', function () {
    expect(Minutes::of(90)->value)->toBe(90)
        ->and(Minutes::zero()->value)->toBe(0);
});

it('rejects negative minutes', function () {
    Minutes::of(-1);
})->throws(InvalidArgumentException::class, 'Minutes cannot be negative, got -1.');

it('adds and subtracts minutes', function () {
    expect(Minutes::of(40)->plus(Minutes::of(20))->value)->toBe(60)
        ->and(Minutes::of(40)->minus(Minutes::of(15))->value)->toBe(25);
});

it('does not subtract into negative minutes', function () {
    Minutes::of(10)->minus(Minutes::of(11));
})->throws(InvalidArgumentException::class);

it('rounds up to the next whole point — RN-01', function (int $minutes, int $rounded) {
    expect(Minutes::of($minutes)->roundedUpToPoint()->value)->toBe($rounded);
})->with([
    'zero stays zero' => [0, 0],
    'one minute is a full point' => [1, 15],
    'exact point is kept' => [15, 15],
    'just over a point' => [16, 30],
    'october 2026 from the spreadsheet' => [1324, 1335],
]);

it('formats minutes as hours and minutes', function () {
    expect(Minutes::of(1324)->formatted())->toBe('22h04')
        ->and(Minutes::zero()->formatted())->toBe('0h00');
});
