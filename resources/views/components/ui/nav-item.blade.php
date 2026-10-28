@props([
    'href',
    'icon',
    'active' => false,
])

<a
    href="{{ $href }}"
    wire:navigate
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'group flex h-9 items-center gap-3 rounded-r-full pr-3 pl-6 text-sm transition-colors duration-150',
        'bg-brand-soft font-medium text-brand' => $active,
        'text-ink hover:bg-zinc-100 dark:hover:bg-white/5' => ! $active,
    ]) }}
>
    <flux:icon :name="$icon" variant="outline" class="size-[18px] shrink-0 {{ $active ? 'text-brand' : 'text-muted group-hover:text-ink' }}" />
    <span class="truncate">{{ $slot }}</span>
</a>
