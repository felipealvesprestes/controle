<div>
    <x-ui.page-header
        :title="$this->role->name"
        :description="trans_choice(':count permission|:count permissions', count($permissions))"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Roles') => route('admin.roles.index'), $this->role->name => null]"
    >
        <x-slot:actions>
            <flux:button wire:click="delete" wire:confirm="{{ __('Delete the role :name?', ['name' => $this->role->name]) }}" class="!text-negative">{{ __('Delete') }}</flux:button>
            <flux:button variant="primary" wire:click="save" data-test="save-role-button">{{ __('Save') }}</flux:button>
        </x-slot:actions>

        <x-slot:tabs>
            @foreach (['permissions' => __('Permissions'), 'members' => __('Users'), 'history' => __('History')] as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['border-b-2 pb-3 text-sm transition-colors duration-150', 'border-brand font-medium text-brand' => $tab === $key, 'border-transparent text-muted hover:text-ink' => $tab !== $key])>{{ $label }}</button>
            @endforeach
        </x-slot:tabs>
    </x-ui.page-header>

    <div class="p-4 sm:p-8">
        <flux:error name="role" class="mb-4" />

        @if ($tab === 'permissions')
            <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:items-start">
                <section class="space-y-4">
                    <flux:input wire:model="name" :label="__('Name')" />
                    <flux:input wire:model="description" :label="__('Description')" />
                    <p class="text-sm text-muted">{{ __('Ticking an item also ticks what it depends on.') }}</p>
                    <flux:error name="permissions" />
                </section>

                <div class="space-y-6">
                    @foreach ($this->catalog()->byModule() as $module => $modulePermissions)
                        <section class="overflow-hidden rounded-lg border border-line bg-surface">
                            <h2 class="flex items-center justify-between border-b border-line px-6 py-3 text-sm font-medium text-ink">
                                {{ __($module) }}
                                <span class="num text-xs font-normal text-muted">{{ __(':done of :total', ['done' => count(array_intersect($permissions, array_map(fn ($p) => $p->key, $modulePermissions))), 'total' => count($modulePermissions)]) }}</span>
                            </h2>
                            <ul class="divide-y divide-line">
                                @foreach ($modulePermissions as $permission)
                                    <li class="px-6 py-3" wire:key="role-perm-{{ $permission->key }}">
                                        <label class="flex items-start gap-3">
                                            <input type="checkbox" value="{{ $permission->key }}" wire:model.live="permissions" class="mt-0.5 size-4 rounded border-zinc-300 accent-[var(--color-brand)]">
                                            <span class="w-20 shrink-0 font-mono text-xs text-muted">{{ __('permission_type.'.$permission->type->value) }}</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm text-ink">{{ __($permission->label) }}</span>
                                                @if ($permission->dependsOn !== [])
                                                    <span class="block text-xs text-muted">{{ __('requires: :list', ['list' => collect($permission->dependsOn)->map(fn ($key) => __($this->catalog()->get($key)->label))->join(', ')]) }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            </div>
        @elseif ($tab === 'members')
            <section class="rounded-lg border border-line bg-surface px-6 py-2">
                @forelse ($this->role->users as $member)
                    <a href="{{ route('admin.users.show', $member) }}" wire:navigate class="flex justify-between gap-4 border-b border-line py-3 text-sm last:border-0">
                        <span class="text-brand">{{ $member->name }}</span>
                        <span class="text-muted">{{ $member->email }}</span>
                    </a>
                @empty
                    <p class="py-3 text-sm text-muted">{{ __('No user has this role.') }}</p>
                @endforelse
            </section>
        @else
            <section class="rounded-lg border border-line bg-surface px-6 py-2">
                <x-ui.audit-list :entries="$this->history" />
            </section>
        @endif
    </div>
</div>
