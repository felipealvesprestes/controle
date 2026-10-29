<div>
    @php($current = $status === 'inactive' ? 'inactive' : 'active')

    <x-ui.page-header
        :title="__('Clients')"
        :description="__('Who is billed, at what rate and with which data for the invoice.')"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Clients') => null]"
    >
        @can('client.create')
            <x-slot:actions>
                <flux:button variant="primary" icon="plus" wire:click="$set('creating', true)" data-test="new-client-button">{{ __('New client') }}</flux:button>
            </x-slot:actions>
        @endcan

        <x-slot:tabs>
            @foreach (['active' => __('Active clients'), 'inactive' => __('Inactive clients')] as $key => $label)
                <button type="button" wire:click="$set('status', '{{ $key }}')" @class(['border-b-2 pb-3 text-sm transition-colors duration-150', 'border-brand font-medium text-brand' => $current === $key, 'border-transparent text-muted hover:text-ink' => $current !== $key])>
                    {{ $label }} <span class="num">({{ $this->counts[$key] }})</span>
                </button>
            @endforeach
        </x-slot:tabs>
    </x-ui.page-header>

    <div class="p-4 sm:p-8">
        @if ($this->rows === [])
            <div class="rounded-lg border border-line bg-surface px-6 py-5 text-sm">
                @if ($current === 'inactive')
                    <p class="text-muted">{{ __('No inactive clients.') }}</p>
                @else
                    <p class="text-ink">{{ __('No clients yet.') }}</p>
                    <p class="mt-1 text-muted">{{ __('Register the first one to log hours for it.') }}</p>
                    @can('client.create')
                        <flux:button class="mt-4" size="sm" icon="plus" wire:click="$set('creating', true)">{{ __('New client') }}</flux:button>
                    @endcan
                @endif
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-line bg-surface">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-muted">
                        <tr class="border-b border-line">
                            <th class="px-6 py-3 font-normal">{{ __('Client') }}</th>
                            <th class="hidden px-6 py-3 font-normal md:table-cell">{{ __('CPF or CNPJ') }}</th>
                            <th class="px-6 py-3 text-right font-normal">{{ __('Rate') }}</th>
                            <th class="hidden px-6 py-3 font-normal lg:table-cell">{{ __('Rounding') }}</th>
                            <th class="hidden px-6 py-3 font-normal sm:table-cell">{{ __('Fiscal data') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($this->rows as $row)
                            <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-white/5" wire:key="client-{{ $row['id'] }}">
                                <td class="px-6 py-3">
                                    <span class="flex items-center gap-2.5">
                                        <x-ui.client-dot :color="$row['color']" />
                                        <a href="{{ route('clients.show', $row['id']) }}" wire:navigate class="font-medium text-brand hover:underline">{{ $row['name'] }}</a>
                                        @unless ($row['fiscalComplete'])
                                            <span class="inline-flex items-center gap-1 text-xs text-muted sm:hidden"><flux:icon.exclamation-triangle variant="micro" class="text-warning" />{{ __('fiscal') }}</span>
                                        @endunless
                                    </span>
                                    @if ($row['legalName'] !== '')
                                        <p class="ps-5 text-muted">{{ $row['legalName'] }}</p>
                                    @endif
                                </td>
                                <td class="hidden px-6 py-3 font-mono text-[13px] text-ink md:table-cell">
                                    <x-ui.guarded-content permission="client.fiscal.view">{{ $row['taxId'] !== '' ? $row['taxId'] : '—' }}</x-ui.guarded-content>
                                </td>
                                <td class="num px-6 py-3 text-right">
                                    <x-ui.guarded-content permission="client.rate.view">
                                        @if ($row['isDefaultRate'])
                                            <span class="text-muted">{{ __('Default') }} · {{ $row['rate'] }}/h</span>
                                        @else
                                            <span class="text-ink">{{ $row['rate'] }}/h</span>
                                        @endif
                                    </x-ui.guarded-content>
                                </td>
                                <td class="hidden px-6 py-3 lg:table-cell">
                                    @if ($row['roundingPolicy'] === null)
                                        <span class="text-muted">{{ __('Default') }} · {{ __('rounding_policy.'.$defaultPolicy->value.'.label') }}</span>
                                    @else
                                        <span class="text-ink">{{ __('rounding_policy.'.$row['roundingPolicy'].'.label') }}</span>
                                    @endif
                                </td>
                                <td class="hidden px-6 py-3 sm:table-cell">
                                    @if ($row['fiscalComplete'])
                                        <span class="inline-flex items-center gap-1.5 text-ink"><flux:icon.check variant="micro" class="text-positive" />{{ __('Complete') }}</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-ink" data-test="fiscal-incomplete"><flux:icon.exclamation-triangle variant="micro" class="text-warning" />{{ __('Incomplete') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @can('client.create')
        <x-ui.side-panel :title="__('New client')" open="creating">
            <form wire:submit="create" class="flex flex-1 flex-col">
                <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
                    <flux:input wire:model="name" :label="__('Short name')" :description="__('How the client appears in lists and in the timer.')" required autocomplete="off" maxlength="60" data-test="client-name-input" />

                    <x-ui.color-picker wire:model="color" :label="__('Colour')" />

                    @can('client.rate.edit')
                        <flux:input.group :label="__('Hourly rate')" :description="__('Empty uses the default of :rate/h.', ['rate' => $defaultRate])" class="max-w-48">
                            <flux:input.group.prefix>R$</flux:input.group.prefix>
                            <flux:input wire:model="rate" inputmode="decimal" autocomplete="off" class="num" data-test="client-rate-input" />
                        </flux:input.group>
                        <div class="max-w-48">
                            <flux:input wire:model="validFrom" type="date" :label="__('Valid from')" />
                        </div>
                    @endcan

                    <p class="text-sm text-muted">{{ __('CPF or CNPJ, legal name and address are filled on the client page.') }}</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
                    <flux:button type="button" x-on:click="open = false">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary" data-test="create-client-button">{{ __('Create client') }}</flux:button>
                </div>
            </form>
        </x-ui.side-panel>
    @endcan
</div>
