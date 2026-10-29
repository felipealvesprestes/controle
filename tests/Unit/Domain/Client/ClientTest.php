<?php

use App\Domain\Address\Address;
use App\Domain\Client\Client;
use App\Domain\Shared\Color;
use App\Domain\Shared\TaxId;

it('lists what the invoice still needs', function () {
    $client = new Client(null, 'WIS', Color::Petrol);

    expect($client->missingFiscalData())->toBe(['legal_name', 'tax_id', 'address'])
        ->and($client->address()->isComplete())->toBeFalse();
});

it('has complete fiscal data with legal name, CPF or CNPJ and the full address', function () {
    $client = new Client(
        id: 1,
        name: 'WIS',
        color: Color::Green,
        legalName: 'WIS Tecnologia LTDA',
        taxId: TaxId::fromInput('11222333000181'),
        address: Address::fromInput(['street' => 'Rua Exemplo', 'number' => '100', 'complement' => '', 'district' => 'Centro', 'city' => 'Canoas', 'state' => 'RS', 'zipCode' => '92010000']),
    );

    expect($client->missingFiscalData())->toBe([])
        ->and($client->toArray())->toMatchArray(['name' => 'WIS', 'color' => 'green', 'rounding_policy' => null, 'document' => '11222333000181', 'city' => 'Canoas', 'active' => true]);
});
