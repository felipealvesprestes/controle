<?php

namespace App\Application\Client;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\ClientRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a client registered by mistake, with its rates (RN-13).
 * Time entries arrive with F-05, which adds the "has entries, deactivate instead" refusal here.
 */
final class DeleteClient
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ClientRepository $clients,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $clientId): void
    {
        if (! $this->authorizer->allows($actorId, 'client.delete')) {
            throw new AccessDenied('You may not delete clients.');
        }

        $client = $this->clients->find($clientId) ?? throw new ClientRuleViolation('client', 'This client no longer exists.');

        DB::transaction(function () use ($actorId, $clientId, $client): void {
            $this->clients->delete($clientId);
            // Only the name stays: the CPF, email and address of a deleted client are not kept (LGPD, data minimisation).
            $this->audit->record($actorId, 'client.deleted', 'client', $clientId, ['name' => $client->name], null);
        });
    }
}
