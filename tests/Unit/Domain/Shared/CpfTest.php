<?php

use App\Domain\Shared\Cpf;

it('accepts a CPF with or without punctuation', function (string $input) {
    expect(Cpf::fromInput($input)->value)->toBe('52998224725');
})->with(['529.982.247-25', '52998224725', ' 529 982 247 25 ']);

it('refuses a CPF with a wrong format', function (string $input) {
    Cpf::fromInput($input);
})->throws(InvalidArgumentException::class, 'Invalid CPF format.')->with(['', '5299822472', '529982247250', '5299822472A', '111.111.111-11']);

it('refuses a CPF with wrong check digits', function (string $input) {
    Cpf::fromInput($input);
})->throws(InvalidArgumentException::class, 'Invalid CPF check digits.')->with(['529.982.247-26', '529.982.247-15']);

it('formats the CPF with punctuation', function () {
    expect(Cpf::fromInput('52998224725')->formatted())->toBe('529.982.247-25');
});
