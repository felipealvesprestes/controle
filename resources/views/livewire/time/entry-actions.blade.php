{{-- Actions of one entry in a list; an entry of a closed period shows a lock instead (RN-10). Expects $entry. --}}
@if ($entry->billingPeriod->status !== 'open')
    <span class="flex size-8 items-center justify-center text-muted" title="{{ __('Period closed: read only') }}" aria-label="{{ __('Period closed: read only') }}">
        <flux:icon.lock-closed variant="micro" />
    </span>
@else
    @canany(['time.entry.create', 'time.entry.delete'])
        <flux:dropdown position="bottom" align="end">
            <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('Actions')" data-test="entry-actions-{{ $entry->id }}" />
            <flux:menu>
                @can('time.entry.create')
                    <flux:menu.item icon="document-duplicate" wire:click="duplicate({{ $entry->id }})">{{ __('Duplicate') }}</flux:menu.item>
                @endcan
                @can('time.entry.delete')
                    <flux:menu.item icon="trash" variant="danger" wire:click="deleteEntry({{ $entry->id }})" data-test="delete-entry-{{ $entry->id }}">{{ __('Delete') }}</flux:menu.item>
                @endcan
            </flux:menu>
        </flux:dropdown>
    @endcanany
@endif
