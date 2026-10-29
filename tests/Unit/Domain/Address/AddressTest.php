<?php

use App\Domain\Address\Address;
use App\Domain\Address\InvalidAddress;

/**
 * @return array{street: string, number: string, complement: string, district: string, city: string, state: string, zipCode: string}
 */
function addressInput(array $overrides = []): array
{
    return ['street' => 'Rua  Exemplo ', 'number' => '100', 'complement' => '', 'district' => 'Centro', 'city' => 'Canoas', 'state' => 'rs', 'zipCode' => '92010-000', ...$overrides];
}

it('trims the parts, upper-cases the state and keeps the CEP digits', function () {
    $address = Address::fromInput(addressInput());

    expect($address->street)->toBe('Rua Exemplo')
        ->and($address->state)->toBe('RS')
        ->and($address->zipCode)->toBe('92010000')
        ->and($address->formattedZipCode())->toBe('92010-000')
        ->and($address->isComplete())->toBeTrue()
        ->and($address->toArray())->toMatchArray(['street' => 'Rua Exemplo', 'zip_code' => '92010000']);
});

it('is incomplete for the invoice while a required part is missing', function (string $field) {
    expect(Address::fromInput(addressInput([$field => '']))->isComplete())->toBeFalse();
})->with(['street', 'number', 'district', 'city', 'state', 'zipCode']);

it('does not need the complement', function () {
    expect(Address::fromInput(addressInput(['complement' => '']))->isComplete())->toBeTrue();
});

it('starts empty', function () {
    expect(Address::empty()->isComplete())->toBeFalse()->and(Address::empty()->formattedZipCode())->toBe('');
});

it('refuses a part that does not fit, a state outside the list and a short CEP', function (array $input, string $field) {
    try {
        Address::fromInput(addressInput($input));
    } catch (InvalidAddress $invalid) {
        expect($invalid->field)->toBe($field);

        return;
    }

    $this->fail('No InvalidAddress thrown.');
})->with([
    'long street' => [['street' => str_repeat('a', 256)], 'street'],
    'long number' => [['number' => str_repeat('1', 21)], 'number'],
    'unknown state' => [['state' => 'XX'], 'state'],
    'short CEP' => [['zipCode' => '9201'], 'zipCode'],
]);
