<?php

namespace App\Domain\Address;

/**
 * Address found for a CEP; any part may be empty (a city-wide CEP has no street or district).
 */
final readonly class AddressSuggestion
{
    public function __construct(
        public string $street,
        public string $district,
        public string $city,
        public string $state,
    ) {}
}
