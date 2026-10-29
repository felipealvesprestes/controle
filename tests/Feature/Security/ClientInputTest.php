<?php

use App\Domain\Client\ClientRate;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\RateSchedule;
use App\Infrastructure\Client\EloquentClientRepository;
use App\Livewire\Clients\ClientShow;
use App\Livewire\Clients\ClientsIndex;
use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;

/**
 * A repository that misses a name saved by someone else a moment earlier, as in two tabs saving at once.
 */
function repositoryBlindToTakenNames(): ClientRepository
{
    $real = app(EloquentClientRepository::class);

    return new class($real) implements ClientRepository
    {
        public function __construct(private readonly EloquentClientRepository $real) {}

        public function nameTaken(string $name, ?int $exceptId = null): bool
        {
            return false;
        }

        public function find(int $id): ?App\Domain\Client\Client
        {
            return $this->real->find($id);
        }

        public function hasActiveClients(): bool
        {
            return $this->real->hasActiveClients();
        }

        public function save(App\Domain\Client\Client $client): int
        {
            return $this->real->save($client);
        }

        public function delete(int $id): void
        {
            $this->real->delete($id);
        }

        public function rates(int $clientId): RateSchedule
        {
            return $this->real->rates($clientId);
        }

        public function ratesOn(DateTimeImmutable $day): array
        {
            return $this->real->ratesOn($day);
        }

        public function addRate(int $clientId, ClientRate $rate): int
        {
            return $this->real->addRate($clientId, $rate);
        }

        public function removeRate(int $clientId, int $rateId): void
        {
            $this->real->removeRate($clientId, $rateId);
        }
    };
}

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    Client::factory()->create(['name' => 'WIS']);
    app()->instance(ClientRepository::class, repositoryBlindToTakenNames());
});

it('refuses a short name taken at the same moment instead of failing with a 500 — A-12', function () {
    Livewire::test(ClientsIndex::class)->set('name', 'WIS')->call('create')->assertHasErrors('name');

    expect(Client::query()->count())->toBe(1);
});

it('refuses renaming to a name taken at the same moment instead of failing with a 500 — A-12', function () {
    $other = Client::factory()->create(['name' => 'Beta']);

    Livewire::test(ClientShow::class, ['client' => $other])->set('name', 'WIS')->call('save')->assertHasErrors('name');

    expect($other->refresh()->name)->toBe('Beta');
});
