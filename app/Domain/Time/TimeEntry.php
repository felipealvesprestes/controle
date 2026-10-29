<?php

namespace App\Domain\Time;

use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Minutes;
use DateTimeImmutable;

/**
 * A piece of work of one person for one client; with no end it is the running timer (F-05, F-06).
 */
final readonly class TimeEntry
{
    public const int DESCRIPTION_LIMIT = 255;

    public const int REFERENCE_LIMIT = 40;

    /**
     * @param  HourlyRate  $rate  The client's rate on the day of the start (RN-04).
     */
    public function __construct(
        public ?int $id,
        public int $userId,
        public int $clientId,
        public int $categoryId,
        public int $billingPeriodId,
        public string $description,
        public ?string $reference,
        public DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $endedAt,
        public HourlyRate $rate,
        public EntrySource $source,
    ) {}

    public function withId(int $id): self
    {
        return new self($id, $this->userId, $this->clientId, $this->categoryId, $this->billingPeriodId, $this->description, $this->reference, $this->startedAt, $this->endedAt, $this->rate, $this->source);
    }

    public function isRunning(): bool
    {
        return $this->endedAt === null;
    }

    /**
     * Null while the timer runs.
     */
    public function minutes(): ?Minutes
    {
        return $this->endedAt === null ? null : Minutes::of(intdiv($this->endedAt->getTimestamp() - $this->startedAt->getTimestamp(), 60));
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'client_id' => $this->clientId,
            'category_id' => $this->categoryId,
            'description' => $this->description,
            'reference' => $this->reference,
            'started_at' => $this->startedAt->format(DATE_ATOM),
            'ended_at' => $this->endedAt?->format(DATE_ATOM),
            'duration_minutes' => $this->minutes()?->value,
            'rate_cents' => $this->rate->perHour->cents,
            'source' => $this->source->value,
        ];
    }
}
