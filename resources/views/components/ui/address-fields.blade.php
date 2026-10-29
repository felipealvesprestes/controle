@props([
    'readOnly' => false,
    'numberId',
])

{{-- Address grid shared by the company and the clients. The CEP sits alone on its row: it fills the rows below, and its spinner stays inside the field.
     Grid placement goes on wrappers: a class on flux:input lands on the <input>, not on the field. --}}
<div class="grid gap-x-5 gap-y-6 sm:grid-cols-6">
    <div class="sm:col-span-2">
        <flux:input wire:model.live.debounce.500ms="zipCode" :label="__('CEP')" inputmode="numeric" autocomplete="postal-code" :disabled="$readOnly" class="font-mono" placeholder="00000-000" data-test="zip-code-input" />
    </div>
    <p class="self-end pb-2.5 text-sm text-muted sm:col-span-4">{{ __('Fills street, district, city and state.') }}</p>

    <div class="sm:col-span-4">
        <flux:input wire:model="street" :label="__('Street')" autocomplete="address-line1" :disabled="$readOnly" />
    </div>
    <div class="sm:col-span-2">
        <flux:input wire:model="number" :id="$numberId" :label="__('Number')" autocomplete="off" :disabled="$readOnly" />
    </div>

    <div class="sm:col-span-3">
        <flux:input wire:model="complement" :label="__('Complement')" autocomplete="address-line2" :disabled="$readOnly" />
    </div>
    <div class="sm:col-span-3">
        <flux:input wire:model="district" :label="__('District')" autocomplete="off" :disabled="$readOnly" />
    </div>

    <div class="sm:col-span-4">
        <flux:input wire:model="city" :label="__('City')" autocomplete="address-level2" :disabled="$readOnly" />
    </div>
    <div class="sm:col-span-2">
        <flux:select wire:model="state" :label="__('State')" :disabled="$readOnly">
            <flux:select.option value="">—</flux:select.option>
            @foreach (\App\Domain\Address\BrazilianStates::CODES as $code)
                <flux:select.option :value="$code">{{ $code }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
</div>
