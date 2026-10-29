<div>
    @php($zone = config('app.display_timezone'))
    @php($timer = $this->timer)

    <x-ui.page-header
        :title="__('Today')"
        :description="now($zone)->translatedFormat('D, d/m')"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Today') => null]"
    >
        <x-slot:actions>
            <p class="num text-sm text-muted" data-test="today-summary">
                <span class="font-medium text-ink">{{ $this->summary['duration'] }}</span>
                @can('time.entry.amount.view')
                    · {{ $this->summary['amount'] }} <span class="text-muted">({{ __('partial') }})</span>
                @endcan
            </p>
            @can('time.entry.create')
                <flux:button variant="primary" icon="plus" wire:click="create" data-test="new-entry-button">{{ __('New entry') }}</flux:button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 p-4 sm:p-8 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:items-start">
        {{-- Timer (F-06). --}}
        @can('time.entry.create')
            <section class="rounded-lg border border-line bg-surface p-5" aria-labelledby="timer-title">
                <h2 id="timer-title" class="sr-only">{{ __('Timer') }}</h2>

                @if ($timer !== null)
                    <p class="flex items-center gap-2 text-sm text-ink">
                        <x-ui.color-dot :color="$timer->client->color" />
                        <span class="truncate font-medium">{{ $timer->client->name }}</span>
                        <span class="text-muted">· {{ $timer->category->name }}</span>
                    </p>
                    <p class="mt-5 text-center text-4xl font-medium tracking-tight text-ink" data-test="timer-elapsed">
                        <x-ui.elapsed :since="$timer->started_at" />
                    </p>
                    <p class="num mt-1 text-center text-xs text-muted">{{ __('since :time', ['time' => $timer->started_at->timezone($zone)->format('H:i')]) }}</p>
                    <flux:error name="timer" class="mt-3" />
                    <flux:button class="mt-5 w-full" variant="primary" icon="stop" wire:click="openStop" data-test="stop-timer-button">{{ __('Stop') }}</flux:button>
                    <div class="mt-3 flex justify-center gap-4 text-sm">
                        <button type="button" wire:click="openAdjust" class="text-brand hover:underline" data-test="adjust-timer-button">{{ __('Adjust start') }}</button>
                        <button type="button" wire:click="discardTimer" wire:confirm="{{ __('Discard the timer? Nothing is logged.') }}" class="text-muted hover:text-ink hover:underline" data-test="discard-timer-button">{{ __('Discard') }}</button>
                    </div>
                @elseif (! $this->hasActiveClients)
                    <p class="text-sm text-ink">{{ __('Register your first client') }}</p>
                    <p class="mt-1 text-sm text-muted">{{ __('Time is always logged for a client.') }}</p>
                    @can('client.view')
                        <flux:button class="mt-4" size="sm" :href="route('clients.index')" wire:navigate>{{ __('Go to clients') }}</flux:button>
                    @endcan
                @else
                    <div class="space-y-4">
                        <flux:select wire:model="timerClientId" :label="__('Client')" data-test="timer-client-input">
                            <flux:select.option value="">{{ __('Choose…') }}</flux:select.option>
                            @foreach ($this->clientOptions as $option)
                                <flux:select.option :value="$option['id']">{{ $option['name'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="timerCategoryId" :label="__('Category')" data-test="timer-category-input">
                            <flux:select.option value="">{{ __('Choose…') }}</flux:select.option>
                            @foreach ($this->categoryOptions as $option)
                                <flux:select.option :value="$option['id']">{{ $option['name'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="timer" />
                        <flux:button class="w-full" variant="primary" icon="play" wire:click="startTimer" data-test="start-timer-button">{{ __('Start') }}</flux:button>
                        @if ($this->lastEntry !== null)
                            <button type="button" wire:click="duplicate({{ $this->lastEntry->id }})" class="block w-full truncate text-center text-sm text-brand hover:underline" data-test="repeat-last-button">
                                {{ __('Repeat last: :entry', ['entry' => $this->lastEntry->client->name.($this->lastEntry->reference ? ' · '.$this->lastEntry->reference : '')]) }}
                            </button>
                        @endif
                    </div>
                @endif
            </section>
        @endcan

        {{-- Entries of today. --}}
        <section class="overflow-hidden rounded-lg border border-line bg-surface" aria-labelledby="entries-title">
            <h2 id="entries-title" class="border-b border-line px-5 py-3 text-sm font-medium text-ink">{{ __('Entries of today') }}</h2>
            <flux:error name="entry" class="px-5 pt-3" />

            @if ($this->entries === [])
                <p class="px-5 py-5 text-sm text-muted">{{ __('No entries today.') }}</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($this->entries as $entry)
                        <li class="flex items-start gap-3 px-5 py-3 text-sm" wire:key="entry-{{ $entry->id }}">
                            <x-ui.color-dot :color="$entry->client->color" class="mt-1.5" />
                            <div class="min-w-0 flex-1">
                                <p class="num flex flex-wrap items-baseline gap-x-3">
                                    @can('time.entry.edit')
                                        <button type="button" wire:click="edit({{ $entry->id }})" class="font-medium text-brand hover:underline">{{ $entry->started_at->timezone($zone)->format('H:i') }}–{{ $entry->ended_at?->timezone($zone)->format('H:i') }}</button>
                                    @else
                                        <span class="font-medium text-ink">{{ $entry->started_at->timezone($zone)->format('H:i') }}–{{ $entry->ended_at?->timezone($zone)->format('H:i') }}</span>
                                    @endcan
                                    <span class="text-ink">{{ \App\Domain\Shared\Minutes::of((int) $entry->duration_minutes)->formatted() }}</span>
                                    <span class="text-muted">{{ $entry->client->name }} · {{ $entry->category->name }}</span>
                                </p>
                                <p class="mt-0.5 truncate text-muted">@if ($entry->reference)<span class="font-mono text-ink">{{ $entry->reference }}</span> @endif{{ $entry->description }}</p>
                            </div>
                            @include('livewire.time.entry-actions', ['entry' => $entry])
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    @include('livewire.time.entry-panel')

    @can('time.entry.create')
        <x-ui.side-panel :title="__('Stop the timer')" open="stopping">
            <form wire:submit="stopTimer" class="flex flex-1 flex-col">
                <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                    <flux:error name="timer" />
                    <flux:input wire:model="stopReference" :label="__('Reference')" :badge="__('Optional')" autocomplete="off" maxlength="40" placeholder="MRP-62" data-test="stop-reference-input" />
                    <flux:input wire:model="stopDescription" :label="__('Description')" autocomplete="off" maxlength="255" data-test="stop-description-input" />
                    @if ($stopEnd === '')
                        <flux:error name="stopEnd" />
                    @else
                        <div>
                            <flux:input wire:model="stopEnd" type="datetime-local" :label="__('End')" data-test="stop-end-input" />
                            <p class="mt-1.5 text-sm text-muted">{{ __('The timer ran for more than 12 hours. When did the work end?') }}</p>
                        </div>
                    @endif
                    @if ($longTimer !== null)
                        <div class="rounded-lg border border-line bg-ground px-4 py-3 text-sm" role="alert">
                            <p class="font-medium text-ink">{{ __('This entry lasts :duration.', ['duration' => $longTimer]) }}</p>
                            <p class="mt-0.5 text-muted">{{ __('Entries over 4 hours are unusual. Save anyway?') }}</p>
                            <flux:button class="mt-3" size="sm" type="button" wire:click="stopTimer(true)" data-test="confirm-long-timer-button">{{ __('Save anyway') }}</flux:button>
                        </div>
                    @endif
                </div>
                <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
                    <flux:button type="button" x-on:click="open = false">{{ __('Keep running') }}</flux:button>
                    <flux:button type="submit" variant="primary" data-test="confirm-stop-button">{{ __('Save entry') }}</flux:button>
                </div>
            </form>
        </x-ui.side-panel>

        <x-ui.side-panel :title="__('Adjust start')" open="adjusting">
            <form wire:submit="adjustTimer" class="flex flex-1 flex-col">
                <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                    <flux:input wire:model="adjustStart" type="datetime-local" :label="__('Started at')" data-test="adjust-start-input" />
                    <flux:error name="timer" />
                    <p class="text-sm text-muted">{{ __('For work that began before the timer did.') }}</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
                    <flux:button type="button" x-on:click="open = false">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary" data-test="confirm-adjust-button">{{ __('Save start') }}</flux:button>
                </div>
            </form>
        </x-ui.side-panel>
    @endcan
</div>
