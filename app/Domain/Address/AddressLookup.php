<?php

namespace App\Domain\Address;

/**
 * Finds the address of a CEP in an external directory.
 */
interface AddressLookup
{
    /**
     * @param  string  $zipCode  Exactly 8 digits.
     * @return AddressSuggestion|null Null when the CEP does not exist.
     *
     * @throws AddressLookupUnavailable When the directory cannot answer.
     */
    public function find(string $zipCode): ?AddressSuggestion;
}
