<div>
    @php($canEdit = auth()->user()->can('category.edit'))

    <x-ui.page-header
        :title="__('Categories')"
        :description="__('The kinds of work, in the order of the entry form. Time in a category that is not billable shows on the report with no amount.')"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Categories') => null]"
    >
        @can('category.create')
            <x-slot:actions>
                <flux:button variant="primary" icon="plus" wire:click="create" data-test="new-category-button">{{ __('New category') }}</flux:button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="max-w-3xl space-y-6 p-4 sm:p-8">
        <flux:error name="category" />

        <section class="overflow-hidden rounded-lg border border-line bg-surface" aria-labelledby="active-title">
            <h2 id="active-title" class="sr-only">{{ __('Active categories') }}</h2>

            @if ($this->active === [])
                <p class="px-6 py-5 text-sm text-muted">{{ __('No active categories. Time cannot be logged until there is one.') }}</p>
            @else
                <ol class="divide-y divide-line" @if ($canEdit) wire:sort="move" @endif data-test="active-categories">
                    @foreach ($this->active as $position => $category)
                        <li class="flex items-center gap-3 bg-surface px-3 py-2.5 text-sm sm:px-4" wire:key="category-{{ $category->id }}" wire:sort:item="{{ $category->id }}">
                            @if ($canEdit)
                                <button type="button" class="flex size-8 cursor-grab items-center justify-center rounded-md text-muted hover:bg-zinc-100 active:cursor-grabbing dark:hover:bg-white/5" wire:sort:handle aria-label="{{ __('Drag to reorder') }}" title="{{ __('Drag to reorder') }}">
                                    <flux:icon.bars-2 variant="micro" />
                                </button>
                            @endif
                            <x-ui.color-dot :color="$category->color->value" />
                            @if ($canEdit)
                                <button type="button" wire:click="edit({{ $category->id }})" class="min-w-0 flex-1 truncate text-left font-medium text-brand hover:underline">{{ $category->name }}</button>
                            @else
                                <span class="min-w-0 flex-1 truncate font-medium text-ink">{{ $category->name }}</span>
                            @endif
                            @unless ($category->billable)
                                <span class="inline-flex items-center gap-1.5 text-muted"><span class="size-2 rounded-full border border-current"></span>{{ __('Not billable') }}</span>
                            @endunless
                            @if ($canEdit)
                                <span class="flex">
                                    <flux:button size="xs" variant="ghost" icon="chevron-up" wire:click="move({{ $category->id }}, {{ $position - 1 }})" :disabled="$loop->first" :aria-label="__('Move :name up', ['name' => $category->name])" />
                                    <flux:button size="xs" variant="ghost" icon="chevron-down" wire:click="move({{ $category->id }}, {{ $position + 1 }})" :disabled="$loop->last" :aria-label="__('Move :name down', ['name' => $category->name])" />
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        @if ($this->inactive !== [])
            <section class="overflow-hidden rounded-lg border border-line bg-surface" aria-labelledby="inactive-title">
                <h2 id="inactive-title" class="border-b border-line px-6 py-3 text-sm font-medium text-ink">{{ __('Inactive') }} <span class="num font-normal text-muted">({{ count($this->inactive) }})</span></h2>
                <ul class="divide-y divide-line">
                    @foreach ($this->inactive as $category)
                        <li class="flex items-center gap-3 px-6 py-2.5 text-sm" wire:key="inactive-{{ $category->id }}">
                            <x-ui.color-dot :color="$category->color->value" class="opacity-60" />
                            <span class="min-w-0 flex-1 truncate text-muted">{{ $category->name }}</span>
                            @can('category.status.edit')
                                <flux:button size="xs" wire:click="setActive({{ $category->id }}, true)" data-test="reactivate-category-{{ $category->id }}">{{ __('Reactivate') }}</flux:button>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    @canany(['category.create', 'category.edit'])
        <x-ui.side-panel :title="$editingId === null ? __('New category') : __('Edit category')" open="editing">
            <form wire:submit="save" class="flex flex-1 flex-col">
                <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
                    <flux:input wire:model="name" :label="__('Name')" required autocomplete="off" maxlength="40" data-test="category-name-input" />
                    <x-ui.color-picker wire:model="color" :label="__('Colour')" />
                    <flux:checkbox wire:model="billable" :label="__('Billable')" :description="__('Unchecked, the time shows on the report with no amount and stays out of the total. Closed periods do not change.')" data-test="category-billable-input" />

                    @if ($editingId !== null)
                        <div class="space-y-3 border-t border-line pt-5">
                            @can('category.status.edit')
                                <div class="flex items-center justify-between gap-4">
                                    <p class="text-sm text-muted">{{ __('Leaves the choices of new entries; its history stays.') }}</p>
                                    <flux:button size="sm" type="button" wire:click="setActive({{ $editingId }}, false)" data-test="deactivate-category-button">{{ __('Deactivate category') }}</flux:button>
                                </div>
                            @endcan
                            @can('category.delete')
                                <div class="flex items-center justify-between gap-4">
                                    <p class="text-sm text-muted">{{ __('Only for a category created by mistake.') }}</p>
                                    <flux:button size="sm" type="button" icon="trash" wire:click="delete" wire:confirm="{{ __('Delete the category :name?', ['name' => $name]) }}" data-test="delete-category-button">{{ __('Delete') }}</flux:button>
                                </div>
                            @endcan
                        </div>
                    @endif
                </div>
                <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
                    <flux:button type="button" x-on:click="open = false">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary" data-test="save-category-button">{{ $editingId === null ? __('Create category') : __('Save category') }}</flux:button>
                </div>
            </form>
        </x-ui.side-panel>
    @endcanany
</div>
