@props([
    'title',
    'description',
])

<div class="flex w-full flex-col">
    <h1 class="text-2xl font-normal tracking-[-0.01em] text-ink">{{ $title }}</h1>
    <p class="mt-1.5 text-sm text-muted">{{ $description }}</p>
</div>
