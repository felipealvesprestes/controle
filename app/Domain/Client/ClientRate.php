<?php

namespace App\Domain\Client;

use App\Domain\Shared\HourlyRate;
use DateTimeImmutable;

/**
 * An hourly rate valid from a given day (RN-04).
 */
final readonly class ClientRate
{
    public function __construct(
        public ?int $id,
        public DateTimeImmutable $validFrom,
        public HourlyRate $rate,
    ) {}

    /**
     * @return array{valid_from: string, rate_cents: int}
     */
    public function toArray(): array
    {
        return ['valid_from' => $this->validFrom->format('Y-m-d'), 'rate_cents' => $this->rate->perHour->cents];
    }
}
