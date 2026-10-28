@props([
    'href',
    'icon',
    'exact' => false,
])

{{-- Active state comes from wire:current, so it updates inside the persisted sidebar. --}}
<a
    href="{{ $href }}"
    wire:navigate
    @if ($exact) wire:current.exact="" @else wire:current="" @endif
    @click="$store.nav.open = false"
    {{ $attributes->class([
        'group flex h-9 items-center gap-3 rounded-r-full pr-3 pl-6 text-sm text-ink transition-colors duration-150',
        'hover:bg-zinc-100 dark:hover:bg-white/5',
        'data-current:bg-brand-soft data-current:font-medium data-current:text-brand',
    ]) }}
>
    <flux:icon :name="$icon" variant="outline" class="size-[18px] shrink-0 text-muted group-hover:text-ink group-data-current:text-brand" />
    <span class="truncate">{{ $slot }}</span>
</a>
