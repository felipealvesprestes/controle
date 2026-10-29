<?php

use App\Domain\Shared\TaxId;

it('reads eleven digits as a CPF, for a person', function () {
    $taxId = TaxId::fromInput('529.982.247-25');

    expect($taxId->isIndividual())->toBeTrue()
        ->and($taxId->value())->toBe('52998224725')
        ->and($taxId->formatted())->toBe('529.982.247-25');
});

it('reads anything else as a CNPJ, numeric or alphanumeric', function (string $input, string $formatted) {
    $taxId = TaxId::fromInput($input);

    expect($taxId->isIndividual())->toBeFalse()->and($taxId->formatted())->toBe($formatted);
})->with([
    ['11222333000181', '11.222.333/0001-81'],
    ['12.abc.345/01de-35', '12.ABC.345/01DE-35'],
]);

it('refuses what is neither a CPF nor a CNPJ', function (string $input) {
    TaxId::fromInput($input);
})->throws(InvalidArgumentException::class)->with(['529.982.247-26', '11.222.333/0001-82', '123']);
