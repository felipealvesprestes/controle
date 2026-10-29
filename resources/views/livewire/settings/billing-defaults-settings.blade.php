<div>
    <x-ui.page-header
        :title="__('Billing defaults')"
        :description="__('Used by every client that does not set its own rate or rounding.')"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Settings') => null, __('Billing defaults') => null]"
    />

    <div class="grid gap-6 p-4 sm:p-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
        <section class="rounded-lg border border-line bg-surface p-6" aria-labelledby="defaults-title">
            <h2 id="defaults-title" class="text-base font-medium text-ink">{{ __('Rate and rounding') }}</h2>

            <form wire:submit="save" class="mt-5 space-y-6">
                @can('settings.billing.rate.view')
                    <flux:input.group :label="__('Default hourly rate')" :description="__('In reais, such as 60,00.')" class="max-w-56">
                        <flux:input.group.prefix>R$</flux:input.group.prefix>
                        <flux:input
                            wire:model.live.debounce.300ms="rate"
                            inputmode="decimal"
                            autocomplete="off"
                            class="num"
                            :disabled="! auth()->user()->can('settings.billing.edit')"
                            data-test="rate-input"
                        />
                    </flux:input.group>
                @else
                    <div>
                        <p class="text-sm font-medium text-ink">{{ __('Default hourly rate') }}</p>
                        <p class="mt-1 text-sm"><x-ui.guarded-content permission="settings.billing.rate.view">—</x-ui.guarded-content></p>
                    </div>
                @endcan

                <flux:radio.group wire:model.live="roundingPolicy" :label="__('Rounding policy')" :disabled="! auth()->user()->can('settings.billing.edit')">
                    @foreach ($policies as $policy)
                        <flux:radio :value="$policy->value" :label="__('rounding_policy.'.$policy->value.'.label')" :description="__('rounding_policy.'.$policy->value.'.description')" />
                    @endforeach
                </flux:radio.group>

                @can('settings.billing.edit')
                    <div class="flex justify-end border-t border-line pt-5">
                        <flux:button type="submit" variant="primary" data-test="save-billing-defaults-button">{{ __('Save defaults') }}</flux:button>
                    </div>
                @endcan
            </form>
        </section>

        {{-- Live example: the domain computes 1h07 with the rate and policy on screen. --}}
        <section class="rounded-lg border border-line bg-surface" aria-labelledby="example-title" aria-live="polite">
            <div class="px-6 pt-5">
                <h2 id="example-title" class="text-base font-medium text-ink">{{ __('Example') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ __('One entry of :duration.', ['duration' => \App\Domain\Shared\Minutes::of(\App\Livewire\Settings\BillingDefaultsSettings::EXAMPLE_MINUTES)->formatted()]) }}</p>
            </div>

            <div class="mt-5 border-t border-line px-6 py-5">
                @if ($this->example)
                    <p class="num text-sm text-ink" data-test="rounding-example">
                        {{ $this->example['worked'] }}
                        →
                        {{ $this->example['points'] === null ? $this->example['billed'] : trans_choice(':count point|:count points', $this->example['points']) }}
                        →
                        <span class="text-brand">{{ $this->example['amount'] }}</span>
                    </p>
                @else
                    <p class="num text-sm text-muted" data-test="rounding-example">
                        <x-ui.guarded-content permission="settings.billing.rate.view">{{ __('Enter a valid rate to see the example.') }}</x-ui.guarded-content>
                    </p>
                @endif
            </div>
        </section>
    </div>
</div>
