<?php

namespace App\Application\Client;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Client\ClientRate;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\ClientRuleViolation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Adds a rate change from a given day (RN-04). Entries already in closed periods keep their snapshot (RN-11).
 */
final class ScheduleRate
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ClientRepository $clients,
        private readonly AuditTrail $audit,
    ) {}

    /**
     * @param  string  $validFrom  YYYY-MM-DD, as sent by the date field.
     */
    public function handle(int $actorId, int $clientId, string $rate, string $validFrom): ClientRate
    {
        if (! $this->authorizer->allows($actorId, 'client.rate.edit')) {
            throw new AccessDenied('You may not change client rates.');
        }

        if ($this->clients->find($clientId) === null) {
            throw new ClientRuleViolation('client', 'This client no longer exists.');
        }

        $scheduled = new ClientRate(null, ClientFields::day($validFrom, 'rateValidFrom'), ClientFields::rate($rate, 'rateValue'));

        // One rate per day and client is kept by the unique index, which also covers two people saving at once.
        try {
            return DB::transaction(function () use ($actorId, $clientId, $scheduled): ClientRate {
                $rateId = $this->clients->addRate($clientId, $scheduled);
                $this->audit->record($actorId, 'client.rate_scheduled', 'client', $clientId, null, $scheduled->toArray());

                return new ClientRate($rateId, $scheduled->validFrom, $scheduled->rate);
            });
        } catch (UniqueConstraintViolationException) {
            throw new ClientRuleViolation('rateValidFrom', 'This client already has a rate starting on this day.');
        }
    }
}
