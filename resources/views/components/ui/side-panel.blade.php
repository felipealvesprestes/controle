@props([
    'title',
    'open',
])

{{-- Right-hand panel for creating without leaving the list (linguagem-visual). --}}
<div x-data="{ open: $wire.entangle('{{ $open }}') }" x-show="open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="{{ $title }}" @keydown.escape.window="open = false">
    <div class="absolute inset-0 bg-zinc-950/40" x-show="open" x-transition.opacity.duration.150ms @click="open = false"></div>
    <div class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-line bg-surface shadow-xl" x-show="open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="translate-x-full">
        <div class="flex h-14 items-center justify-between border-b border-line px-6">
            <h2 class="text-base font-medium text-ink">{{ $title }}</h2>
            <button type="button" class="flex size-9 items-center justify-center rounded-full text-muted hover:bg-zinc-100 dark:hover:bg-white/5" @click="open = false" aria-label="{{ __('Close') }}">
                <flux:icon.x-mark variant="outline" class="size-5" />
            </button>
        </div>
        {{ $slot }}
    </div>
</div>
