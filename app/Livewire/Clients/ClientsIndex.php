<?php

namespace App\Livewire\Clients;

use App\Application\Client\CreateClient;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\ClientRuleViolation;
use App\Domain\Provider\ProviderProfileRepository;
use App\Infrastructure\Client\EloquentClientRepository;
use App\Models\Client;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * T-08: active and inactive clients, with their current rate and the creation panel (F-03).
 */
class ClientsIndex extends Component
{
    #[Url]
    public string $status = 'active';

    public bool $creating = false;

    public string $name = '';

    public string $color = 'petrol';

    public string $rate = '';

    public string $validFrom = '';

    public function mount(): void
    {
        $this->validFrom = self::today()->format('Y-m-d');
    }

    /**
     * @return list<array{id: int, name: string, color: string, legalName: string, taxId: string, rate: string, isDefaultRate: bool, roundingPolicy: string|null, fiscalComplete: bool}>
     */
    #[Computed]
    public function rows(): array
    {
        $current = app(ClientRepository::class)->ratesOn(self::today());
        $default = app(ProviderProfileRepository::class)->billingDefaults();
        $seesFiscalData = Gate::allows('client.fiscal.view');
        $rows = [];

        foreach (Client::query()->where('active', $this->status !== 'inactive')->orderBy('name')->get() as $model) {
            $client = EloquentClientRepository::toDomain($model);
            $rate = $current[$model->id] ?? null;

            $rows[] = [
                'id' => $model->id,
                'name' => $client->name,
                'color' => $client->color->value,
                // An individual's full name is personal data, like the CPF (F-03, Privacidade).
                'legalName' => $seesFiscalData ? $client->legalName : '',
                'taxId' => $client->taxId?->formatted() ?? '',
                'rate' => ($rate === null ? $default->rate : $rate->rate)->perHour->formatted(),
                'isDefaultRate' => $rate === null,
                'roundingPolicy' => $client->roundingPolicy?->value,
                'fiscalComplete' => $client->missingFiscalData() === [],
            ];
        }

        return $rows;
    }

    /**
     * @return array{active: int, inactive: int}
     */
    #[Computed]
    public function counts(): array
    {
        $active = Client::query()->where('active', true)->count();

        return ['active' => $active, 'inactive' => Client::query()->count() - $active];
    }

    public function create(CreateClient $createClient): void
    {
        try {
            $clientId = $createClient->handle((int) Auth::id(), $this->name, $this->color, $this->rate, $this->validFrom);
        } catch (ClientRuleViolation $violation) {
            $this->resetErrorBag();
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        $this->redirectRoute('clients.show', ['client' => $clientId], navigate: true);
    }

    private static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(Config::string('app.display_timezone'))->startOfDay();
    }

    public function render(): View
    {
        $defaults = app(ProviderProfileRepository::class)->billingDefaults();

        return view('livewire.clients.clients-index', [
            'defaultPolicy' => $defaults->roundingPolicy,
            'defaultRate' => $defaults->rate->perHour->formatted(),
        ])->title(__('Clients'));
    }
}
