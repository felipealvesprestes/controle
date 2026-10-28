{{-- Settings section: a label column beside a white panel (stacks on the phone). --}}
<div class="grid gap-6 p-4 sm:p-8 lg:grid-cols-[280px_minmax(0,640px)]">
    <div>
        <h2 class="text-base font-medium text-ink">{{ $heading ?? '' }}</h2>
        <p class="mt-1 text-sm text-muted">{{ $subheading ?? '' }}</p>
    </div>

    <div class="rounded-lg border border-line bg-surface p-6">
        {{ $slot }}
    </div>
</div>
