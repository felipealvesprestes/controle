<?php

namespace App\Domain\Time;

use DateTimeImmutable;

/**
 * Time entries and running timers (F-05, F-06).
 */
interface TimeEntryRepository
{
    public function find(int $id): ?TimeEntry;

    /**
     * Holds the person's writes until the transaction ends, so two saves cannot both pass the overlap check (RN-03, RN-14).
     */
    public function lockOwner(int $userId): void;

    /**
     * The earliest entry of the person that overlaps the interval; touching ends do not count (RN-03).
     * A running timer and an interval with no end both reach into the future.
     */
    public function firstOverlapping(int $userId, DateTimeImmutable $start, ?DateTimeImmutable $end, ?int $exceptId = null): ?TimeEntry;

    public function runningTimer(int $userId): ?TimeEntry;

    /**
     * Creates the entry when it has no id yet.
     *
     * @return int The entry id.
     */
    public function save(TimeEntry $entry): int;

    public function delete(int $id): void;

    public function userHasEntries(int $userId): bool;

    public function clientHasEntries(int $clientId): bool;

    public function categoryHasEntries(int $categoryId): bool;
}
