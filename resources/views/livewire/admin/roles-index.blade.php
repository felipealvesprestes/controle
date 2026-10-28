<div>
    <x-ui.page-header
        :title="__('Roles')"
        :description="__('Each role is a set of permissions you give to users.')"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Administration') => null, __('Roles') => null]"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="$set('creating', true)" data-test="create-role-button">{{ __('Create role') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="p-4 sm:p-8">
        @if ($this->roles->isEmpty())
            <div class="rounded-lg border border-dashed border-line bg-surface px-6 py-8">
                <p class="text-sm text-ink">{{ __('No roles yet.') }}</p>
                <p class="mt-1 text-sm text-muted">{{ __('Create a role such as "Collaborator" or "Accountant" and choose, item by item, what it can see and do.') }}</p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-line bg-surface">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-muted">
                        <tr class="border-b border-line">
                            <th class="px-6 py-3 font-normal">{{ __('Role') }}</th>
                            <th class="px-6 py-3 text-right font-normal">{{ __('Users') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($this->roles as $role)
                            <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-white/5" wire:key="role-{{ $role->id }}">
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.roles.show', $role) }}" wire:navigate class="font-medium text-brand hover:underline">{{ $role->name }}</a>
                                    @if ($role->description !== '')
                                        <p class="text-muted">{{ $role->description }}</p>
                                    @endif
                                </td>
                                <td class="num px-6 py-3 text-right text-ink">{{ $role->users_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <x-ui.side-panel :title="__('Create role')" open="creating">
        <form wire:submit="create" class="flex flex-1 flex-col">
            <div class="flex-1 space-y-5 px-6 py-6">
                <flux:input wire:model="name" :label="__('Name')" required autocomplete="off" />
                <flux:input wire:model="description" :label="__('Description')" autocomplete="off" />
                <p class="text-sm text-muted">{{ __('You choose the permissions on the next screen. A new role starts with none.') }}</p>
            </div>
            <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
                <flux:button type="button" x-on:click="open = false">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Create role') }}</flux:button>
            </div>
        </form>
    </x-ui.side-panel>
</div>
