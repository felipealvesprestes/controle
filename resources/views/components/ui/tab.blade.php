@props([
    'href',
    'active' => false,
])

<a
    href="{{ $href }}"
    wire:navigate
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'border-b-2 pb-3 text-sm transition-colors duration-150',
        'border-brand font-medium text-brand' => $active,
        'border-transparent text-muted hover:text-ink' => ! $active,
    ]) }}
>{{ $slot }}</a>
