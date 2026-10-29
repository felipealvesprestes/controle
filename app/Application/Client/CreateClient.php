<?php

namespace App\Application\Client;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Client\Client;
use App\Domain\Client\ClientRate;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\ClientRuleViolation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Creates a client from its short name and colour, with an optional first rate (F-03, RN-04).
 * The fiscal data comes later, on the client page.
 */
final class CreateClient
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ClientRepository $clients,
        private readonly ClientFields $fields,
        private readonly AuditTrail $audit,
    ) {}

    /**
     * @param  string  $rate  Empty to use the provider default.
     * @param  string  $validFrom  Day the first rate starts (YYYY-MM-DD).
     * @return int The new client id.
     */
    public function handle(int $actorId, string $name, string $color, string $rate, string $validFrom): int
    {
        if (! $this->authorizer->allows($actorId, 'client.create')) {
            throw new AccessDenied('You may not create clients.');
        }

        $client = new Client(null, $this->fields->name($name), ClientFields::color($color));
        $firstRate = null;

        if (trim($rate) !== '') {
            if (! $this->authorizer->allows($actorId, 'client.rate.edit')) {
                throw new AccessDenied('You may not set client rates.');
            }

            $firstRate = new ClientRate(null, ClientFields::day($validFrom, 'validFrom'), ClientFields::rate($rate));
        }

        // The unique index settles two people creating the same name at once (A-12).
        try {
            return DB::transaction(function () use ($actorId, $client, $firstRate): int {
                $clientId = $this->clients->save($client);
                $this->audit->record($actorId, 'client.created', 'client', $clientId, null, $client->toArray());

                if ($firstRate !== null) {
                    $this->clients->addRate($clientId, $firstRate);
                    $this->audit->record($actorId, 'client.rate_scheduled', 'client', $clientId, null, $firstRate->toArray());
                }

                return $clientId;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ClientRuleViolation('name', 'Another client already uses this name.');
        }
    }
}
