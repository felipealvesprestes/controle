@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

{{-- Tinted header band: breadcrumb, h1, optional line and actions, optional tabs. --}}
<header class="border-b border-line bg-brand-band">
    <div class="px-4 pt-5 sm:px-8 {{ isset($tabs) ? 'pb-0' : 'pb-6' }}">
        @if ($breadcrumbs !== [])
            <nav aria-label="{{ __('Breadcrumb') }}" class="mb-2 flex items-center gap-1.5 text-sm text-muted">
                @foreach ($breadcrumbs as $label => $url)
                    @if ($url)
                        <a href="{{ $url }}" wire:navigate class="hover:text-ink">{{ $label }}</a>
                    @else
                        <span>{{ $label }}</span>
                    @endif
                    @unless ($loop->last)
                        <flux:icon.chevron-right variant="micro" class="text-muted" />
                    @endunless
                @endforeach
            </nav>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <div class="min-w-0">
                <h1 class="text-2xl font-normal tracking-[-0.01em] text-ink sm:text-[28px]">{{ $title }}</h1>
                @if ($description)
                    <p class="mt-1 text-sm text-muted">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>

        @isset($tabs)
            <nav class="mt-4 -mb-px flex gap-6" aria-label="{{ __('Sections') }}">{{ $tabs }}</nav>
        @endisset
    </div>
</header>
