<div>
    @php($zone = config('app.display_timezone'))
    @php($filtered = $filterClientId !== '' || $filterCategoryId !== '' || trim($search) !== '')

    <x-ui.page-header
        :title="__('Time entries')"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Time entries') => null]"
    >
        @can('time.entry.create')
            <x-slot:actions>
                <flux:button variant="primary" icon="plus" wire:click="create" data-test="new-entry-button">{{ __('New entry') }}</flux:button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="space-y-4 p-4 sm:p-8">
        <div class="flex flex-wrap items-end gap-3">
            <flux:input wire:model.live="month" type="month" :label="__('Month')" class="max-w-44" data-test="filter-month" />
            <flux:select wire:model.live="filterClientId" :label="__('Client')" class="max-w-52" data-test="filter-client">
                <flux:select.option value="">{{ __('All clients') }}</flux:select.option>
                @foreach ($this->filterOptions['clients'] as $option)
                    <flux:select.option :value="$option['id']">{{ $option['name'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="filterCategoryId" :label="__('Category')" class="max-w-52" data-test="filter-category">
                <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                @foreach ($this->filterOptions['categories'] as $option)
                    <flux:select.option :value="$option['id']">{{ $option['name'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live.debounce.300ms="search" type="search" :label="__('Search')" :placeholder="__('Description or reference')" icon="magnifying-glass" class="min-w-48 flex-1" data-test="filter-search" />
        </div>

        <p class="num text-sm text-muted" data-test="entries-totals">
            <span class="font-medium text-ink">{{ $this->totals['duration'] }}</span> · {{ trans_choice(':count entry|:count entries', $this->totals['count']) }}
        </p>

        <flux:error name="entry" />

        @if ($this->days === [])
            <div class="rounded-lg border border-line bg-surface px-6 py-5 text-sm">
                @if ($filtered)
                    <p class="text-ink">{{ __('Nothing found.') }}</p>
                    <flux:button class="mt-3" size="sm" wire:click="clearFilters" data-test="clear-filters-button">{{ __('Clear filters') }}</flux:button>
                @else
                    <p class="text-muted">{{ __('No entries in this month.') }}</p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border border-line bg-surface">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-muted">
                        <tr class="border-b border-line">
                            <th class="px-4 py-3 font-normal">{{ __('Time') }}</th>
                            <th class="px-4 py-3 font-normal">{{ __('Client') }}</th>
                            <th class="hidden px-4 py-3 font-normal md:table-cell">{{ __('Category') }}</th>
                            <th class="px-4 py-3 font-normal">{{ __('Description') }}</th>
                            @if ($this->seesEveryone)
                                <th class="hidden px-4 py-3 font-normal lg:table-cell">{{ __('User') }}</th>
                            @endif
                            <th class="px-4 py-3 text-right font-normal">{{ __('Duration') }}</th>
                            @can('time.entry.amount.view')
                                <th class="hidden px-4 py-3 text-right font-normal sm:table-cell">{{ __('Amount') }}</th>
                            @endcan
                            <th class="w-10 px-2 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    @foreach ($this->days as $day => $entries)
                        <tbody class="divide-y divide-line border-b border-line last:border-b-0" wire:key="day-{{ $day }}">
                            <tr class="bg-ground">
                                <th colspan="20" class="px-4 py-2 text-xs font-medium text-ink">
                                    <span class="flex justify-between">
                                        <span>{{ \Carbon\CarbonImmutable::parse($day, $zone)->translatedFormat('D, d/m') }}</span>
                                        <span class="num">{{ \App\Livewire\Time\TimeEntriesIndex::sum($entries) }}</span>
                                    </span>
                                </th>
                            </tr>
                            @foreach ($entries as $entry)
                                @php($open = $entry->billingPeriod->status === 'open')
                                <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-white/5" wire:key="entry-{{ $entry->id }}">
                                    <td class="num px-4 py-2.5 whitespace-nowrap">
                                        <button type="button" wire:click="edit({{ $entry->id }})" @class(['hover:underline', 'text-brand' => $open && auth()->user()->can('time.entry.edit'), 'text-ink' => ! $open || auth()->user()->cannot('time.entry.edit')])>{{ $entry->started_at->timezone($zone)->format('H:i') }}–{{ $entry->ended_at?->timezone($zone)->format('H:i') }}</button>
                                    </td>
                                    <td class="px-4 py-2.5"><span class="flex items-center gap-2"><x-ui.color-dot :color="$entry->client->color" /><span class="truncate">{{ $entry->client->name }}</span></span></td>
                                    <td class="hidden px-4 py-2.5 text-muted md:table-cell">{{ $entry->category->name }}</td>
                                    <td class="max-w-md px-4 py-2.5"><span class="line-clamp-2">@if ($entry->reference)<span class="font-mono text-ink">{{ $entry->reference }}</span> @endif<span class="text-muted">{{ $entry->description }}</span></span></td>
                                    @if ($this->seesEveryone)
                                        <td class="hidden px-4 py-2.5 text-muted lg:table-cell">{{ $entry->user->name }}</td>
                                    @endif
                                    <td class="num px-4 py-2.5 text-right">{{ \App\Domain\Shared\Minutes::of((int) $entry->duration_minutes)->formatted() }}</td>
                                    @can('time.entry.amount.view')
                                        <td class="num hidden px-4 py-2.5 text-right text-muted sm:table-cell">{{ \App\Domain\Shared\Money::fromCentMinutes($entry->rate_cents * (int) $entry->duration_minutes)->formatted() }}</td>
                                    @endcan
                                    <td class="px-2 py-2.5 text-right">@include('livewire.time.entry-actions', ['entry' => $entry])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </div>

    @include('livewire.time.entry-panel')
</div>
