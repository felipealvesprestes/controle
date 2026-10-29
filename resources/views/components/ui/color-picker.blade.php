@props([
    'label',
    'disabled' => false,
])

{{-- Client colour as native radios: keyboard arrows move between swatches, the ring marks the chosen one (F-03). --}}
<fieldset {{ $attributes->only('data-test') }}>
    <legend class="text-sm font-medium text-ink">{{ $label }}</legend>
    <div class="mt-3 flex flex-wrap gap-1.5">
        @foreach (\App\Domain\Client\ClientColor::cases() as $option)
            <label class="relative cursor-pointer has-disabled:cursor-default" title="{{ __('client_color.'.$option->value) }}">
                <input type="radio" {{ $attributes->whereStartsWith('wire:model') }} value="{{ $option->value }}" class="peer sr-only" @disabled($disabled) aria-label="{{ __('client_color.'.$option->value) }}">
                <span class="flex size-8 items-center justify-center rounded-full ring-ink transition-shadow peer-checked:ring-2 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand peer-disabled:opacity-60">
                    <x-ui.client-dot :color="$option->value" class="size-4" />
                </span>
            </label>
        @endforeach
    </div>
    <flux:error name="color" />
</fieldset>
