<?php

namespace App\Domain\Client;

use DateTimeImmutable;

/**
 * A client's rate history: each rate holds from its start day until the next one starts (RN-04).
 */
final readonly class RateSchedule
{
    /** @var list<ClientRate> Newest first. */
    public array $rates;

    /**
     * @param  list<ClientRate>  $rates
     */
    public function __construct(array $rates)
    {
        usort($rates, static fn (ClientRate $a, ClientRate $b): int => $b->validFrom <=> $a->validFrom);
        $this->rates = $rates;
    }

    /**
     * The rate valid on the given day, or null when the client uses the provider default (RN-04).
     */
    public function on(DateTimeImmutable $day): ?ClientRate
    {
        $date = $day->format('Y-m-d');

        foreach ($this->rates as $rate) {
            if ($rate->validFrom->format('Y-m-d') <= $date) {
                return $rate;
            }
        }

        return null;
    }

    public function find(int $id): ?ClientRate
    {
        foreach ($this->rates as $rate) {
            if ($rate->id === $id) {
                return $rate;
            }
        }

        return null;
    }
}
