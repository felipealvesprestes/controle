<div>
    @php($readOnly = ! auth()->user()->can('client.edit'))
    @php($missing = $this->client->missingFiscalData())

    <x-ui.page-header
        :title="$this->client->name"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Clients') => route('clients.index'), $this->client->name => null]"
    >
        <x-slot:actions>
            @can('client.status.edit')
                @if ($this->client->active)
                    <flux:button wire:click="setActive(false)" wire:confirm="{{ __('Deactivate :name? It leaves the choices of new entries; its history stays.', ['name' => $this->client->name]) }}" data-test="deactivate-client-button">{{ __('Deactivate client') }}</flux:button>
                @else
                    <flux:button wire:click="setActive(true)" data-test="reactivate-client-button">{{ __('Reactivate') }}</flux:button>
                @endif
            @endcan
            @can('client.delete')
                <flux:button icon="trash" wire:click="delete" wire:confirm.prompt="{{ __('Delete :name and its rates? This cannot be undone.', ['name' => $this->client->name]) }}\n\n{{ __('Type :word to confirm', ['word' => __('DELETE')]) }}|{{ __('DELETE') }}" data-test="delete-client-button">{{ __('Delete') }}</flux:button>
            @endcan
        </x-slot:actions>

        <x-slot:tabs>
            @foreach (['details' => __('Details'), 'history' => __('History')] as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['border-b-2 pb-3 text-sm transition-colors duration-150', 'border-brand font-medium text-brand' => $tab === $key, 'border-transparent text-muted hover:text-ink' => $tab !== $key])>{{ $label }}</button>
            @endforeach
        </x-slot:tabs>
    </x-ui.page-header>

    <div class="p-4 sm:p-8">
        <flux:error name="client" class="mb-4" />

        @if ($tab === 'details')
            <div class="mb-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                <span class="inline-flex items-center gap-1.5 text-ink">
                    @if ($this->client->active)
                        <span class="size-2 rounded-full bg-positive"></span>{{ __('Active') }}
                    @else
                        <span class="size-2 rounded-full bg-zinc-400"></span>{{ __('Inactive') }}
                    @endif
                </span>
                @if ($missing === [])
                    <span class="inline-flex items-center gap-1.5 text-ink"><flux:icon.check variant="micro" class="text-positive" />{{ __('Fiscal data complete') }}</span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-ink" data-test="fiscal-incomplete">
                        <flux:icon.exclamation-triangle variant="micro" class="text-warning" />
                        {{ __('Fiscal data incomplete') }}:
                        <span class="text-muted">{{ collect($missing)->map(fn ($item) => __('client_fiscal.'.$item))->join(', ') }}</span>
                    </span>
                @endif
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
                <form wire:submit="save" class="space-y-6">
                    <section class="rounded-lg border border-line bg-surface p-6" aria-labelledby="client-title">
                        <h2 id="client-title" class="text-base font-medium text-ink">{{ __('Client') }}</h2>

                        <div class="mt-6 grid gap-x-5 gap-y-6 sm:grid-cols-2">
                            <flux:input wire:model="name" :label="__('Short name')" required autocomplete="off" maxlength="60" :disabled="$readOnly" data-test="client-name-input" />
                            <flux:select wire:model="roundingPolicy" :label="__('Rounding policy')" :disabled="$readOnly">
                                <flux:select.option value="">{{ __('Use the default (:policy)', ['policy' => __('rounding_policy.'.$this->defaults->roundingPolicy->value.'.label')]) }}</flux:select.option>
                                @foreach ($policies as $policy)
                                    <flux:select.option :value="$policy->value">{{ __('rounding_policy.'.$policy->value.'.label') }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <div class="sm:col-span-2">
                                <x-ui.color-picker wire:model="color" :label="__('Colour')" :disabled="$readOnly" />
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-line bg-surface p-6" aria-labelledby="fiscal-title">
                        <h2 id="fiscal-title" class="text-base font-medium text-ink">{{ __('Fiscal data') }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ __('Who receives the invoice.') }}</p>

                        @can('client.fiscal.view')
                            <div class="mt-6 grid gap-x-5 gap-y-6 sm:grid-cols-2">
                                <flux:input wire:model="taxId" :label="__('CPF or CNPJ')" autocomplete="off" :disabled="$readOnly" class="font-mono" placeholder="000.000.000-00" data-test="tax-id-input" />
                                <flux:input wire:model="email" type="email" :label="__('Email')" placeholder="financeiro@empresa.com.br" autocomplete="off" :disabled="$readOnly" />
                                <div class="sm:col-span-2">
                                    <flux:input wire:model="legalName" :label="__('Legal name or full name')" autocomplete="off" :disabled="$readOnly" />
                                </div>
                                <div class="sm:col-span-2">
                                    <flux:checkbox wire:model="withholdsIss" :label="__('Withholds ISS')" :description="__('The client pays the ISS to the city and deducts it from the payment.')" :disabled="$readOnly" />
                                </div>
                            </div>
                        @else
                            <p class="mt-4 text-sm"><x-ui.guarded-content permission="client.fiscal.view">—</x-ui.guarded-content></p>
                        @endcan
                    </section>

                    <section class="rounded-lg border border-line bg-surface p-6" aria-labelledby="address-title">
                        <h2 id="address-title" class="text-base font-medium text-ink">{{ __('Address') }}</h2>

                        @can('client.fiscal.view')
                            @if ($this->client->taxId?->isIndividual())
                                <p class="mt-1 text-sm text-muted">{{ __('The CEP is sent to ViaCEP to fill the address.') }}</p>
                            @endif
                            <div class="mt-6">
                                <x-ui.address-fields :read-only="$readOnly" number-id="client-number" />
                            </div>
                        @else
                            <p class="mt-4 text-sm"><x-ui.guarded-content permission="client.fiscal.view">—</x-ui.guarded-content></p>
                        @endcan
                    </section>

                    @unless ($readOnly)
                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary" data-test="save-client-button">{{ __('Save client') }}</flux:button>
                        </div>
                    @endunless
                </form>

                <section class="rounded-lg border border-line bg-surface" aria-labelledby="rates-title">
                    <div class="px-6 pt-5">
                        <h2 id="rates-title" class="text-base font-medium text-ink">{{ __('Rates') }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ __('Each rate holds from its day until the next one starts.') }}</p>
                    </div>

                    @can('client.rate.view')
                        <ol class="mt-4 divide-y divide-line border-t border-line" data-test="rates">
                            @forelse ($this->rates->rates as $rate)
                                <li class="flex items-center justify-between gap-4 px-6 py-3 text-sm" wire:key="rate-{{ $rate->id }}">
                                    <span class="num text-muted">{{ __('from :date', ['date' => $rate->validFrom->format('d/m/Y')]) }}</span>
                                    <span class="flex items-center gap-3">
                                        <span @class(['num', 'font-medium text-brand' => $rate->id === $this->currentRateId, 'text-ink' => $rate->id !== $this->currentRateId])>{{ $rate->rate->perHour->formatted() }}/h</span>
                                        @if ($rate->id === $this->currentRateId)
                                            <span class="rounded-full bg-brand-soft px-2 py-0.5 text-xs text-brand">{{ __('Current') }}</span>
                                        @endif
                                        @can('client.rate.edit')
                                            <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeRate({{ $rate->id }})" wire:confirm="{{ __('Remove the rate from :date?', ['date' => $rate->validFrom->format('d/m/Y')]) }}" :aria-label="__('Remove rate')" />
                                        @endcan
                                    </span>
                                </li>
                            @empty
                                <li class="px-6 py-3 text-sm text-muted">{{ __('Default: :rate/h', ['rate' => $this->defaults->rate->perHour->formatted()]) }}</li>
                            @endforelse
                            @if ($this->rates->rates !== [] && $this->currentRateId === null)
                                <li class="px-6 py-3 text-sm text-muted">{{ __('Until the first one starts: default of :rate/h', ['rate' => $this->defaults->rate->perHour->formatted()]) }}</li>
                            @endif
                        </ol>
                        <flux:error name="rates" class="px-6 pb-3" />

                        @can('client.rate.edit')
                            <form wire:submit="scheduleRate" class="border-t border-line px-6 py-5">
                                <p class="text-sm font-medium text-ink">{{ __('New rate change') }}</p>
                                <div class="mt-4 space-y-6">
                                    <flux:input.group :label="__('Hourly rate')" class="max-w-48">
                                        <flux:input.group.prefix>R$</flux:input.group.prefix>
                                        <flux:input wire:model="rateValue" inputmode="decimal" autocomplete="off" class="num" data-test="rate-value-input" />
                                    </flux:input.group>
                                    <div class="max-w-48">
                                        <flux:input wire:model="rateValidFrom" type="date" :label="__('Valid from')" data-test="rate-valid-from-input" />
                                    </div>
                                </div>
                                <div class="mt-5 flex justify-end">
                                    <flux:button type="submit" size="sm" data-test="schedule-rate-button">{{ __('Schedule rate') }}</flux:button>
                                </div>
                            </form>
                        @endcan
                    @else
                        <p class="border-t border-line px-6 py-4 text-sm"><x-ui.guarded-content permission="client.rate.view">—</x-ui.guarded-content></p>
                    @endcan
                </section>
            </div>
        @else
            <section class="rounded-lg border border-line bg-surface px-6 py-2">
                <x-ui.audit-list :entries="$this->history" />
            </section>
        @endif
    </div>
</div>
