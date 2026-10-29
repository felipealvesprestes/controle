<?php

namespace App\Livewire\Clients;

use App\Application\Client\ChangeClientStatus;
use App\Application\Client\DeleteClient;
use App\Application\Client\RemoveRate;
use App\Application\Client\ScheduleRate;
use App\Application\Client\UpdateClient;
use App\Domain\Billing\Rounding\RoundingPolicyType;
use App\Domain\Client\Client;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\ClientRuleViolation;
use App\Domain\Client\RateSchedule;
use App\Domain\Provider\BillingDefaults;
use App\Domain\Provider\ProviderProfileRepository;
use App\Livewire\Concerns\FillsAddressFromZipCode;
use App\Models\AuditLog;
use App\Models\Client as ClientModel;
use Carbon\CarbonImmutable;
use Closure;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * T-08: one client's details, rate history, fiscal data and history (F-03).
 */
class ClientShow extends Component
{
    use FillsAddressFromZipCode;

    #[Locked]
    public int $clientId;

    public string $tab = 'details';

    public string $name = '';

    public string $color = '';

    /** Empty uses the provider default (RN-06). */
    public string $roundingPolicy = '';

    /** Fiscal fields stay empty unless the user may see them: public properties reach the browser. */
    public string $legalName = '';

    public string $taxId = '';

    public string $email = '';

    public bool $withholdsIss = false;

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $district = '';

    public string $city = '';

    public string $state = '';

    public string $zipCode = '';

    public string $rateValue = '';

    public string $rateValidFrom = '';

    public function mount(ClientModel $client): void
    {
        $this->clientId = $client->id;
        $this->fillFrom($this->client());
        $this->rateValidFrom = self::today()->format('Y-m-d');
    }

    #[Computed]
    public function client(): Client
    {
        return app(ClientRepository::class)->find($this->clientId) ?? abort(404);
    }

    #[Computed]
    public function rates(): RateSchedule
    {
        return app(ClientRepository::class)->rates($this->clientId);
    }

    #[Computed]
    public function currentRateId(): ?int
    {
        return $this->rates()->on(self::today())?->id;
    }

    #[Computed]
    public function defaults(): BillingDefaults
    {
        return app(ProviderProfileRepository::class)->billingDefaults();
    }

    /**
     * @return Collection<int, AuditLog>
     */
    #[Computed]
    public function history(): Collection
    {
        return AuditLog::query()->with('actor')->where('subject_type', 'client')->where('subject_id', $this->clientId)->latest('id')->limit(30)->get();
    }

    public function updatedZipCode(): void
    {
        $this->fillAddressFromZipCode('client.edit', 'client-number');
    }

    public function save(UpdateClient $updateClient): void
    {
        $this->attempt(function () use ($updateClient): void {
            $this->fillFrom($updateClient->handle((int) Auth::id(), $this->clientId, [
                'name' => $this->name,
                'color' => $this->color,
                'roundingPolicy' => $this->roundingPolicy,
                'legalName' => $this->legalName,
                'taxId' => $this->taxId,
                'email' => $this->email,
                'withholdsIss' => $this->withholdsIss,
                'street' => $this->street,
                'number' => $this->number,
                'complement' => $this->complement,
                'district' => $this->district,
                'city' => $this->city,
                'state' => $this->state,
                'zipCode' => $this->zipCode,
            ]));
        }, __('Client saved.'));
    }

    public function scheduleRate(ScheduleRate $scheduleRate): void
    {
        $this->attempt(function () use ($scheduleRate): void {
            $scheduleRate->handle((int) Auth::id(), $this->clientId, $this->rateValue, $this->rateValidFrom);
            $this->rateValue = '';
        }, __('Rate scheduled.'));
    }

    public function removeRate(RemoveRate $removeRate, int $rateId): void
    {
        $this->attempt(fn () => $removeRate->handle((int) Auth::id(), $this->clientId, $rateId), __('Rate removed.'));
    }

    public function setActive(ChangeClientStatus $changeClientStatus, bool $active): void
    {
        $this->attempt(fn () => $changeClientStatus->handle((int) Auth::id(), $this->clientId, $active), $active ? __('Client reactivated.') : __('Client deactivated.'));
    }

    public function delete(DeleteClient $deleteClient): void
    {
        try {
            $deleteClient->handle((int) Auth::id(), $this->clientId);
        } catch (ClientRuleViolation $violation) {
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        Flux::toast(variant: 'success', text: __('Client deleted.'));
        $this->redirectRoute('clients.index', navigate: true);
    }

    private function attempt(Closure $action, string $success): void
    {
        try {
            $action();
        } catch (ClientRuleViolation $violation) {
            $this->resetErrorBag();
            $this->addError($violation->field, __($violation->getMessage()));

            return;
        }

        $this->resetErrorBag();
        unset($this->client, $this->rates, $this->currentRateId, $this->history);
        Flux::toast(variant: 'success', text: $success);
    }

    private function fillFrom(Client $client): void
    {
        $this->name = $client->name;
        $this->color = $client->color->value;
        $this->roundingPolicy = $client->roundingPolicy->value ?? '';

        if (Gate::denies('client.fiscal.view')) {
            return;
        }

        $address = $client->address();
        $this->legalName = $client->legalName;
        $this->taxId = $client->taxId?->formatted() ?? '';
        $this->email = $client->email;
        $this->withholdsIss = $client->withholdsIss;
        $this->street = $address->street;
        $this->number = $address->number;
        $this->complement = $address->complement;
        $this->district = $address->district;
        $this->city = $address->city;
        $this->state = $address->state;
        $this->zipCode = $address->formattedZipCode();
    }

    private static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(Config::string('app.display_timezone'))->startOfDay();
    }

    public function render(): View
    {
        return view('livewire.clients.client-show', [
            'policies' => RoundingPolicyType::cases(),
        ])->title($this->client()->name);
    }
}
