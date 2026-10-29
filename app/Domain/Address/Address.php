<?php

namespace App\Domain\Address;

/**
 * A Brazilian postal address; every part may be empty until the invoice needs it.
 */
final readonly class Address
{
    /** Longest value of each part, as stored. */
    public const array LIMITS = ['street' => 255, 'number' => 20, 'complement' => 100, 'district' => 100, 'city' => 100];

    private function __construct(
        public string $street,
        public string $number,
        public string $complement,
        public string $district,
        public string $city,
        public string $state,
        public string $zipCode,
    ) {}

    public static function empty(): self
    {
        return new self('', '', '', '', '', '', '');
    }

    /**
     * Trims every part, upper-cases the state and keeps only the digits of the CEP.
     *
     * @param  array{street: string, number: string, complement: string, district: string, city: string, state: string, zipCode: string}  $input
     *
     * @throws InvalidAddress
     */
    public static function fromInput(array $input): self
    {
        $text = array_map(static fn (string $value): string => trim((string) preg_replace('/\s+/u', ' ', $value)), $input);

        foreach (self::LIMITS as $field => $limit) {
            if (mb_strlen($text[$field]) > $limit) {
                throw new InvalidAddress($field, 'Too long for this field.');
            }
        }

        $state = strtoupper($text['state']);

        if ($state !== '' && ! in_array($state, BrazilianStates::CODES, true)) {
            throw new InvalidAddress('state', 'Choose a state.');
        }

        $zipCode = (string) preg_replace('/\D/', '', $text['zipCode']);

        if ($text['zipCode'] !== '' && strlen($zipCode) !== 8) {
            throw new InvalidAddress('zipCode', 'Enter the 8 digits of the CEP.');
        }

        return new self($text['street'], $text['number'], $text['complement'], $text['district'], $text['city'], $state, $zipCode);
    }

    /**
     * Everything the invoice asks for; the complement is optional.
     */
    public function isComplete(): bool
    {
        return $this->street !== '' && $this->number !== '' && $this->district !== '' && $this->city !== '' && $this->state !== '' && $this->zipCode !== '';
    }

    /**
     * 92010-000, or empty.
     */
    public function formattedZipCode(): string
    {
        return $this->zipCode === '' ? '' : substr($this->zipCode, 0, 5).'-'.substr($this->zipCode, 5);
    }

    /**
     * @return array{street: string, number: string, complement: string, district: string, city: string, state: string, zip_code: string}
     */
    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'district' => $this->district,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zipCode,
        ];
    }
}
