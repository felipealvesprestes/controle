<?php

use App\Domain\Shared\Money;

it('stores money as whole cents — RN-07', function () {
    expect(Money::fromCents(133500)->cents)->toBe(133500)
        ->and(Money::zero()->cents)->toBe(0);
});

it('adds and compares amounts', function () {
    $total = Money::fromCents(150)->plus(Money::fromCents(250));

    expect($total->equals(Money::fromCents(400)))->toBeTrue()
        ->and($total->equals(Money::fromCents(401)))->toBeFalse();
});

it('rounds cent-minutes half up to the cent exactly once — RN-07', function (int $centMinutes, int $cents) {
    expect(Money::fromCentMinutes($centMinutes)->cents)->toBe($cents);
})->with([
    'exact cents' => [60, 1],
    'below half rounds down' => [89, 1],
    'half rounds up' => [90, 2],
    'one hour at R$ 60' => [6000 * 60, 6000],
]);

it('rejects negative cent-minutes', function () {
    Money::fromCentMinutes(-1);
})->throws(InvalidArgumentException::class, 'Cent-minutes cannot be negative, got -1.');

it('reads amounts typed the Brazilian way', function (string $input, int $cents) {
    expect(Money::parse($input)->cents)->toBe($cents);
})->with([
    'whole reais' => ['60', 6000],
    'one decimal' => ['60,5', 6050],
    'two decimals' => ['60,50', 6050],
    'thousands separator' => ['1.234,56', 123456],
    'currency prefix and spaces' => [' R$ 75,00 ', 7500],
]);

it('refuses amounts that are not typed the Brazilian way', function (string $input) {
    Money::parse($input);
})->throws(InvalidArgumentException::class, 'Invalid amount.')->with(['', 'abc', '60.50', '60,555', '-10', '1.23,00', '1234567890']);

it('formats amounts in reais', function () {
    expect(Money::fromCents(133500)->formatted())->toBe('R$ 1.335,00')
        ->and(Money::fromCents(1507)->decimal())->toBe('15,07');
});
