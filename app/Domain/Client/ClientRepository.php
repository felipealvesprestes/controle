<?php

namespace App\Domain\Client;

use DateTimeImmutable;

/**
 * Clients and their rate history (F-03, RN-04).
 */
interface ClientRepository
{
    public function find(int $id): ?Client;

    public function nameTaken(string $name, ?int $exceptId = null): bool;

    public function hasActiveClients(): bool;

    /**
     * Creates the client when it has no id yet.
     *
     * @return int The client id.
     */
    public function save(Client $client): int;

    public function delete(int $id): void;

    public function rates(int $clientId): RateSchedule;

    /**
     * The rate valid on the given day for every client that has one (RN-04).
     *
     * @return array<int, ClientRate> Keyed by client id.
     */
    public function ratesOn(DateTimeImmutable $day): array;

    /**
     * @return int The rate id.
     */
    public function addRate(int $clientId, ClientRate $rate): int;

    public function removeRate(int $clientId, int $rateId): void;
}
