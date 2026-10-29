<?php

namespace App\Application\Client;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\ClientRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * Removes a rate typed by mistake (RN-04). Closed periods keep their own snapshot (RN-11).
 */
final class RemoveRate
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ClientRepository $clients,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $clientId, int $rateId): void
    {
        if (! $this->authorizer->allows($actorId, 'client.rate.edit')) {
            throw new AccessDenied('You may not change client rates.');
        }

        $rate = $this->clients->rates($clientId)->find($rateId)
            ?? throw new ClientRuleViolation('rates', 'This rate no longer exists.');

        DB::transaction(function () use ($actorId, $clientId, $rateId, $rate): void {
            $this->clients->removeRate($clientId, $rateId);
            $this->audit->record($actorId, 'client.rate_removed', 'client', $clientId, $rate->toArray(), null);
        });
    }
}
