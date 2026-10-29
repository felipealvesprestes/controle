<?php

namespace App\Application\Time;

use DateTimeImmutable;

/**
 * What the person types for an entry (T-04); with no end it describes the running timer.
 */
final readonly class EntryDetails
{
    /**
     * @param  bool  $longConfirmed  The person confirmed an entry longer than four hours (RN-02).
     */
    public function __construct(
        public int $clientId,
        public int $categoryId,
        public string $description,
        public string $reference,
        public DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $endedAt,
        public bool $longConfirmed = false,
    ) {}
}
