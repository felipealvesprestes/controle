<?php

namespace App\Application\Client;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Client\Client;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\ClientRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * Deactivates or reactivates a client. An inactive client keeps its history but leaves the choices of new entries (RN-13).
 */
final class ChangeClientStatus
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ClientRepository $clients,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $clientId, bool $active): void
    {
        if (! $this->authorizer->allows($actorId, 'client.status.edit')) {
            throw new AccessDenied('You may not change the client status.');
        }

        $client = $this->clients->find($clientId) ?? throw new ClientRuleViolation('client', 'This client no longer exists.');

        if ($client->active === $active) {
            return;
        }

        $changed = new Client($client->id, $client->name, $client->color, $client->roundingPolicy, $client->legalName, $client->taxId, $client->email, $client->withholdsIss, $client->address, $active);

        DB::transaction(function () use ($actorId, $clientId, $changed, $active): void {
            $this->clients->save($changed);
            $this->audit->record($actorId, $active ? 'client.reactivated' : 'client.deactivated', 'client', $clientId);
        });
    }
}
