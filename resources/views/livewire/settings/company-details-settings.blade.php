<div>
    <x-ui.page-header
        :title="__('Company details')"
        :description="__('How the company appears on the report and on the invoice.')"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Settings') => null, __('Company details') => null]"
    />

    @php($readOnly = ! auth()->user()->can('settings.company.edit'))

    <form wire:submit="save" class="max-w-4xl space-y-6 p-4 sm:p-8">
        <section class="rounded-lg border border-line bg-surface p-6" aria-labelledby="company-title">
            <h2 id="company-title" class="text-base font-medium text-ink">{{ __('Company') }}</h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="legalName" :label="__('Legal name')" required autocomplete="organization" :disabled="$readOnly" class="sm:col-span-2" />
                <flux:input wire:model="tradeName" :label="__('Trade name')" autocomplete="off" :disabled="$readOnly" class="sm:col-span-2" />
                <flux:input wire:model="cnpj" :label="__('CNPJ')" required autocomplete="off" :disabled="$readOnly" class="font-mono" placeholder="00.000.000/0000-00" data-test="cnpj-input" />
                <flux:input wire:model="municipalRegistration" :label="__('Municipal registration')" autocomplete="off" :disabled="$readOnly" />
                <div class="sm:col-span-2">
                    <p class="text-sm font-medium text-ink">{{ __('Tax regime') }}</p>
                    <p class="mt-1 text-sm text-muted">{{ __('tax_regime.simples_me_epp') }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-line bg-surface p-6" aria-labelledby="address-title">
            <h2 id="address-title" class="text-base font-medium text-ink">{{ __('Address') }}</h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-6">
                <flux:input wire:model="zipCode" :label="__('CEP')" inputmode="numeric" autocomplete="postal-code" :disabled="$readOnly" class="font-mono sm:col-span-2" placeholder="00000-000" />
                <flux:input wire:model="street" :label="__('Street')" autocomplete="address-line1" :disabled="$readOnly" class="sm:col-span-4" />
                <flux:input wire:model="number" :label="__('Number')" autocomplete="off" :disabled="$readOnly" class="sm:col-span-2" />
                <flux:input wire:model="complement" :label="__('Complement')" autocomplete="address-line2" :disabled="$readOnly" class="sm:col-span-4" />
                <flux:input wire:model="district" :label="__('District')" autocomplete="off" :disabled="$readOnly" class="sm:col-span-2" />
                <flux:input wire:model="city" :label="__('City')" autocomplete="address-level2" :disabled="$readOnly" class="sm:col-span-3" />
                <flux:select wire:model="state" :label="__('State')" :disabled="$readOnly" class="sm:col-span-1">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach ($states as $state)
                        <flux:select.option :value="$state">{{ $state }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </section>

        @unless ($readOnly)
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" data-test="save-company-details-button">{{ __('Save company details') }}</flux:button>
            </div>
        @endunless
    </form>
</div>
