<?php

use App\Domain\Shared\Cnpj;

it('accepts numeric and alphanumeric CNPJ with or without punctuation', function (string $input, string $value) {
    expect(Cnpj::fromInput($input)->value)->toBe($value);
})->with([
    'numeric with punctuation' => ['11.222.333/0001-81', '11222333000181'],
    'numeric digits only' => ['11222333000181', '11222333000181'],
    'alphanumeric, Receita example' => ['12.ABC.345/01DE-35', '12ABC34501DE35'],
    'alphanumeric in lower case' => ['12abc34501de35', '12ABC34501DE35'],
]);

it('refuses a CNPJ with a wrong format', function (string $input) {
    Cnpj::fromInput($input);
})->throws(InvalidArgumentException::class, 'Invalid CNPJ format.')->with(['', '1122233300018', '112223330001811', '11222333000_81', '12ABC34501DEAB', '00.000.000/0000-00']);

it('refuses a CNPJ with wrong check digits', function (string $input) {
    Cnpj::fromInput($input);
})->throws(InvalidArgumentException::class, 'Invalid CNPJ check digits.')->with(['11.222.333/0001-82', '11.222.333/0001-91', '12.ABC.345/01DE-36']);

it('formats the CNPJ with punctuation', function () {
    expect(Cnpj::fromInput('12abc34501de35')->formatted())->toBe('12.ABC.345/01DE-35');
});
