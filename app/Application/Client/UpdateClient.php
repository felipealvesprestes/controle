<?php

namespace App\Application\Client;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Address\Address;
use App\Domain\Address\InvalidAddress;
use App\Domain\Billing\Rounding\RoundingPolicyType;
use App\Domain\Client\Client;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\ClientRuleViolation;
use App\Domain\Shared\TaxId;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Changes a client's details and fiscal data (F-03). Status and rates have their own use cases.
 */
final class UpdateClient
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ClientRepository $clients,
        private readonly ClientFields $fields,
        private readonly AuditTrail $audit,
    ) {}

    /**
     * An empty rounding policy uses the provider default (RN-06).
     *
     * @param  array{name: string, color: string, roundingPolicy: string, legalName: string, taxId: string, email: string, withholdsIss: bool, street: string, number: string, complement: string, district: string, city: string, state: string, zipCode: string}  $input
     */
    public function handle(int $actorId, int $clientId, array $input): Client
    {
        if (! $this->authorizer->allows($actorId, 'client.edit')) {
            throw new AccessDenied('You may not change clients.');
        }

        $before = $this->clients->find($clientId) ?? throw new ClientRuleViolation('client', 'This client no longer exists.');

        $roundingPolicy = null;

        if ($input['roundingPolicy'] !== '') {
            $roundingPolicy = RoundingPolicyType::tryFrom($input['roundingPolicy'])
                ?? throw new ClientRuleViolation('roundingPolicy', 'Choose a rounding policy.');
        }

        $legalName = ClientFields::text($input['legalName']);

        if (mb_strlen($legalName) > 255) {
            throw new ClientRuleViolation('legalName', 'Too long for this field.');
        }

        $taxId = null;

        if (trim($input['taxId']) !== '') {
            try {
                $taxId = TaxId::fromInput($input['taxId']);
            } catch (InvalidArgumentException) {
                throw new ClientRuleViolation('taxId', 'Enter a valid CPF or CNPJ.');
            }
        }

        $email = mb_strtolower(trim($input['email']));

        if ($email !== '' && (mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            throw new ClientRuleViolation('email', 'Enter a valid email address.');
        }

        try {
            $address = Address::fromInput([
                'street' => $input['street'],
                'number' => $input['number'],
                'complement' => $input['complement'],
                'district' => $input['district'],
                'city' => $input['city'],
                'state' => $input['state'],
                'zipCode' => $input['zipCode'],
            ]);
        } catch (InvalidAddress $invalid) {
            throw new ClientRuleViolation($invalid->field, $invalid->getMessage());
        }

        $client = new Client(
            id: $clientId,
            name: $this->fields->name($input['name'], $clientId),
            color: ClientFields::color($input['color']),
            roundingPolicy: $roundingPolicy,
            legalName: $legalName,
            taxId: $taxId,
            email: $email,
            withholdsIss: $input['withholdsIss'],
            address: $address,
            active: $before->active,
        );

        // The unique index settles two people choosing the same name at once (A-12).
        try {
            DB::transaction(function () use ($actorId, $clientId, $before, $client): void {
                $this->clients->save($client);
                $this->audit->record($actorId, 'client.updated', 'client', $clientId, $before->toArray(), $client->toArray());
            });
        } catch (UniqueConstraintViolationException) {
            throw new ClientRuleViolation('name', 'Another client already uses this name.');
        }

        return $client;
    }
}
