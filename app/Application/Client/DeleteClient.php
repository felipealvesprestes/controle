<?php

namespace App\Application\Client;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Billing\BillingPeriodRepository;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\ClientRuleViolation;
use App\Domain\Time\TimeEntryRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a client registered by mistake, with its rates and empty periods; one with time entries can only be deactivated (RN-13).
 */
final class DeleteClient
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ClientRepository $clients,
        private readonly TimeEntryRepository $entries,
        private readonly BillingPeriodRepository $periods,
        private readonly AuditTrail $audit,
    ) {}

    public function handle(int $actorId, int $clientId): void
    {
        if (! $this->authorizer->allows($actorId, 'client.delete')) {
            throw new AccessDenied('You may not delete clients.');
        }

        $client = $this->clients->find($clientId) ?? throw new ClientRuleViolation('client', 'This client no longer exists.');

        $hasEntries = new ClientRuleViolation('client', 'This client has time entries. Deactivate it instead.');

        try {
            DB::transaction(function () use ($actorId, $clientId, $client, $hasEntries): void {
                if ($this->entries->clientHasEntries($clientId)) {
                    throw $hasEntries;
                }

                $this->periods->deleteForClient($clientId);
                $this->clients->delete($clientId);
                // Only the name stays: the CPF, email and address of a deleted client are not kept (LGPD, data minimisation).
                $this->audit->record($actorId, 'client.deleted', 'client', $clientId, ['name' => $client->name], null);
            });
        } catch (QueryException $exception) {
            // MySQL 1451: an entry arrived between the check and the delete, and the foreign key kept the client (A-16).
            throw ($exception->errorInfo[1] ?? null) === 1451 ? $hasEntries : $exception;
        }
    }
}
