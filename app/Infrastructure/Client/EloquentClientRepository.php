<?php

namespace App\Infrastructure\Client;

use App\Domain\Address\Address;
use App\Domain\Billing\Rounding\RoundingPolicyType;
use App\Domain\Client\Client;
use App\Domain\Client\ClientRate;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\RateSchedule;
use App\Domain\Shared\Color;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Money;
use App\Domain\Shared\TaxId;
use App\Models\Client as ClientModel;
use App\Models\ClientRate as ClientRateModel;
use DateTimeImmutable;

final class EloquentClientRepository implements ClientRepository
{
    public function find(int $id): ?Client
    {
        $model = ClientModel::query()->find($id);

        return $model === null ? null : self::toDomain($model);
    }

    public function nameTaken(string $name, ?int $exceptId = null): bool
    {
        return ClientModel::query()->where('name', $name)->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))->exists();
    }

    public function hasActiveClients(): bool
    {
        return ClientModel::query()->where('active', true)->exists();
    }

    public function save(Client $client): int
    {
        $model = $client->id === null ? new ClientModel : ClientModel::query()->findOrFail($client->id);
        $address = $client->address();

        $model->forceFill([
            'name' => $client->name,
            'color' => $client->color->value,
            'rounding_policy' => $client->roundingPolicy?->value,
            'legal_name' => $client->legalName,
            'document' => $client->taxId?->value(),
            'email' => $client->email,
            'withholds_iss' => $client->withholdsIss,
            'address_street' => $address->street,
            'address_number' => $address->number,
            'address_complement' => $address->complement,
            'address_district' => $address->district,
            'address_city' => $address->city,
            'address_state' => $address->state,
            'address_zip_code' => $address->zipCode,
            'active' => $client->active,
        ])->save();

        return $model->id;
    }

    public function delete(int $id): void
    {
        ClientModel::query()->whereKey($id)->delete();
    }

    public function rates(int $clientId): RateSchedule
    {
        return new RateSchedule(array_values(ClientRateModel::query()->where('client_id', $clientId)->get()->map(self::rateToDomain(...))->all()));
    }

    public function ratesOn(DateTimeImmutable $day): array
    {
        $current = [];

        foreach (ClientRateModel::query()->whereDate('valid_from', '<=', $day->format('Y-m-d'))->orderByDesc('valid_from')->get() as $rate) {
            $current[$rate->client_id] ??= self::rateToDomain($rate);
        }

        return $current;
    }

    public function addRate(int $clientId, ClientRate $rate): int
    {
        $model = new ClientRateModel;
        $model->forceFill([
            'client_id' => $clientId,
            'rate_cents' => $rate->rate->perHour->cents,
            'valid_from' => $rate->validFrom->format('Y-m-d'),
        ])->save();

        return $model->id;
    }

    public function removeRate(int $clientId, int $rateId): void
    {
        ClientRateModel::query()->where('client_id', $clientId)->whereKey($rateId)->delete();
    }

    private static function rateToDomain(ClientRateModel $rate): ClientRate
    {
        return new ClientRate($rate->id, $rate->valid_from, HourlyRate::of(Money::fromCents($rate->rate_cents)));
    }

    /**
     * Rebuilds the client from what the use cases stored, which was validated before saving.
     */
    public static function toDomain(ClientModel $model): Client
    {
        return new Client(
            id: $model->id,
            name: $model->name,
            color: Color::from($model->color),
            roundingPolicy: $model->rounding_policy === null ? null : RoundingPolicyType::from($model->rounding_policy),
            legalName: $model->legal_name,
            taxId: $model->document === null ? null : TaxId::fromInput($model->document),
            email: $model->email,
            withholdsIss: $model->withholds_iss,
            address: Address::fromInput([
                'street' => $model->address_street,
                'number' => $model->address_number,
                'complement' => $model->address_complement,
                'district' => $model->address_district,
                'city' => $model->address_city,
                'state' => $model->address_state,
                'zipCode' => $model->address_zip_code,
            ]),
            active: $model->active,
        );
    }
}
