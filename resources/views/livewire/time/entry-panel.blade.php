{{-- T-04: log or change an entry (F-05). Included by T-02 and T-03, which use EditsTimeEntries. --}}
@canany(['time.entry.create', 'time.entry.edit'])
    <x-ui.side-panel :title="$editingId === null ? __('New entry') : ($readOnly ? __('Entry') : __('Edit entry'))" open="editing">
        <form wire:submit="save" class="flex flex-1 flex-col">
            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                @if ($readOnly)
                    <p class="flex items-start gap-2 rounded-lg border border-line bg-ground px-4 py-3 text-sm text-muted"><flux:icon.lock-closed variant="micro" class="mt-0.5 shrink-0" />{{ __('This entry belongs to a period that is no longer open. Reopen the period to change it.') }}</p>
                @endif

                <flux:error name="entry" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:select wire:model.live="clientId" :label="__('Client')" :disabled="$readOnly" data-test="entry-client-input">
                        <flux:select.option value="">{{ __('Choose…') }}</flux:select.option>
                        @foreach ($this->clientOptions as $option)
                            <flux:select.option :value="$option['id']">{{ $option['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="categoryId" :label="__('Category')" :disabled="$readOnly" data-test="entry-category-input">
                        <flux:select.option value="">{{ __('Choose…') }}</flux:select.option>
                        @foreach ($this->categoryOptions as $option)
                            <flux:select.option :value="$option['id']">{{ $option['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model.blur="reference" :label="__('Reference')" :badge="__('Optional')" list="entry-references" autocomplete="off" maxlength="40" placeholder="MRP-62" :disabled="$readOnly" data-test="entry-reference-input" />
                <datalist id="entry-references">
                    @foreach ($this->suggestions['references'] as $suggestion)
                        <option value="{{ $suggestion }}"></option>
                    @endforeach
                </datalist>

                <flux:input wire:model="description" :label="__('Description')" list="entry-descriptions" autocomplete="off" maxlength="255" :disabled="$readOnly" data-test="entry-description-input" />
                <datalist id="entry-descriptions">
                    @foreach ($this->suggestions['descriptions'] as $suggestion)
                        <option value="{{ $suggestion }}"></option>
                    @endforeach
                </datalist>

                <flux:input wire:model.live.blur="date" type="date" :label="__('Date')" :disabled="$readOnly" data-test="entry-date-input" />

                <div class="grid grid-cols-2 gap-5">
                    <flux:input wire:model.live.blur="start" type="time" :label="__('Start')" :disabled="$readOnly" data-test="entry-start-input" />
                    <flux:input wire:model.live.blur="end" type="time" :label="__('End')" :disabled="$readOnly" data-test="entry-end-input" />
                </div>

                @if ($this->endsBeforeStart || $endsNextDay)
                    <flux:checkbox wire:model.live="endsNextDay" :label="__('Ended the next day?')" :disabled="$readOnly" data-test="entry-next-day-input" />
                @endif

                @if ($conflictId !== null)
                    <div class="text-sm">
                        <flux:button size="xs" type="button" wire:click="edit({{ $conflictId }})">{{ __('Open the conflicting entry') }}</flux:button>
                    </div>
                @endif

                @if ($this->preview !== null)
                    <p class="num text-sm text-muted" data-test="entry-preview">
                        {{ __('Duration') }} <span class="text-ink">{{ $this->preview['duration'] }}</span>
                        · {{ trans_choice(':count pt|:count pts', $this->preview['points']) }}
                        @can('time.entry.amount.view')
                            · {{ $this->preview['amount'] }}
                        @endcan
                    </p>
                @endif

                @if ($longDuration !== null)
                    <div class="rounded-lg border border-line bg-ground px-4 py-3 text-sm" role="alert">
                        <p class="font-medium text-ink">{{ __('This entry lasts :duration.', ['duration' => $longDuration]) }}</p>
                        <p class="mt-0.5 text-muted">{{ __('Entries over 4 hours are unusual. Save anyway?') }}</p>
                        <flux:button class="mt-3" size="sm" type="button" wire:click="confirmLong" data-test="confirm-long-button">{{ __('Save anyway') }}</flux:button>
                    </div>
                @endif
            </div>
            <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
                <flux:button type="button" x-on:click="open = false">{{ $readOnly ? __('Close') : __('Cancel') }}</flux:button>
                @unless ($readOnly)
                    <flux:button type="submit" variant="primary" data-test="save-entry-button">{{ __('Save') }}</flux:button>
                @endunless
            </div>
        </form>
    </x-ui.side-panel>
@endcanany

@if ($deleted !== null)
    <div wire:key="undo-{{ $deleted['token'] }}" x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition.opacity class="fixed inset-x-4 bottom-4 z-40 mx-auto flex max-w-sm items-center justify-between gap-4 rounded-lg bg-zinc-900 px-4 py-3 text-sm text-white shadow-lg dark:bg-zinc-100 dark:text-zinc-900" role="status">
        <span>{{ __('Entry deleted.') }}</span>
        <button type="button" wire:click="undoDelete" class="font-medium text-brand-soft hover:underline dark:text-brand" data-test="undo-delete-button">{{ __('Undo') }}</button>
    </div>
@endif
