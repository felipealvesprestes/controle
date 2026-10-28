@props(['entries'])

{{-- Access history (RN-20): who did what, and when. --}}
@if ($entries->isEmpty())
    <p class="rounded-md border border-dashed border-line px-4 py-3 text-sm text-muted">{{ __('No changes recorded yet.') }}</p>
@else
    <ol class="divide-y divide-line">
        @foreach ($entries as $entry)
            <li class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 py-3 text-sm">
                <span class="text-ink">{{ __('audit.'.$entry->event) }}</span>
                <span class="text-muted">
                    {{ $entry->actor?->name ?? __('Removed user') }} ·
                    <time class="num" datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</time>
                </span>
            </li>
        @endforeach
    </ol>
@endif
