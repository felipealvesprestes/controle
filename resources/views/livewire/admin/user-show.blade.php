<div>
    <x-ui.page-header
        :title="$this->user->name"
        :description="$this->user->email"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Users') => route('admin.users.index'), $this->user->name => null]"
    >
        <x-slot:actions>
            @if ($this->user->active)
                <flux:button wire:click="setActive(false)" wire:confirm="{{ __('Deactivate :name? Their sessions end now.', ['name' => $this->user->name]) }}" data-test="deactivate-user-button">{{ __('Deactivate') }}</flux:button>
            @else
                <flux:button wire:click="setActive(true)" data-test="reactivate-user-button">{{ __('Reactivate') }}</flux:button>
            @endif
        </x-slot:actions>

        <x-slot:tabs>
            @foreach (['details' => __('Details'), 'permissions' => __('Permissions'), 'history' => __('History')] as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['border-b-2 pb-3 text-sm transition-colors duration-150', 'border-brand font-medium text-brand' => $tab === $key, 'border-transparent text-muted hover:text-ink' => $tab !== $key])>{{ $label }}</button>
            @endforeach
        </x-slot:tabs>
    </x-ui.page-header>

    <div class="p-4 sm:p-8">
        <flux:error name="user" class="mb-4" />

        @if ($tab === 'details')
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-start">
                <section class="rounded-lg border border-line bg-surface p-6">
                    <h2 class="text-base font-medium text-ink">{{ __('Account') }}</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('Status') }}</dt><dd class="text-ink">{{ $this->user->active ? __('Active') : __('Inactive') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('Two-factor authentication') }}</dt><dd class="text-ink">{{ $this->user->two_factor_confirmed_at ? __('Configured') : __('Not configured') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('Last access') }}</dt><dd class="num text-ink">{{ $this->user->last_login_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    </dl>

                    <div class="mt-6 flex items-center justify-between gap-4 border-t border-line pt-5">
                        <div>
                            <p class="text-sm font-medium text-ink">{{ __('Administrator') }}</p>
                            <p class="text-sm text-muted">{{ __('Full access, including users and roles.') }}</p>
                        </div>
                        @if ($this->user->is_admin)
                            <flux:button size="sm" wire:click="setAdministrator(false)" wire:confirm="{{ __('Remove administrator access from :name?', ['name' => $this->user->name]) }}">{{ __('Remove') }}</flux:button>
                        @else
                            <flux:button size="sm" wire:click="setAdministrator(true)" wire:confirm="{{ __('Give :name full access?', ['name' => $this->user->name]) }}">{{ __('Make administrator') }}</flux:button>
                        @endif
                    </div>
                </section>

                <section class="rounded-lg border border-line bg-surface p-6">
                    <h2 class="text-base font-medium text-ink">{{ __('Roles') }}</h2>
                    <form wire:submit="saveRoles" class="mt-4 space-y-4">
                        <flux:checkbox.group wire:model="roleIds">
                            @forelse ($this->roles as $role)
                                <flux:checkbox :value="$role->id" :label="$role->name" :description="$role->description" />
                            @empty
                                <p class="text-sm text-muted">{{ __('No roles yet.') }} <a href="{{ route('admin.roles.index') }}" wire:navigate class="text-brand hover:underline">{{ __('Create a role') }}</a></p>
                            @endforelse
                        </flux:checkbox.group>
                        <flux:error name="roles" />
                        @if ($this->roles->isNotEmpty())
                            <div class="flex justify-end"><flux:button type="submit" variant="primary" size="sm">{{ __('Save roles') }}</flux:button></div>
                        @endif
                    </form>
                </section>
            </div>
        @elseif ($tab === 'permissions')
            <div class="space-y-6">
                @if ($this->user->is_admin)
                    <p class="rounded-md border border-line bg-surface px-4 py-3 text-sm text-muted">{{ __('Administrators have every permission; exceptions do not apply to them.') }}</p>
                @endif
                <flux:error name="permission" />

                @foreach ($this->catalog()->byModule() as $module => $permissions)
                    <section class="overflow-hidden rounded-lg border border-line bg-surface">
                        <h2 class="border-b border-line px-6 py-3 text-sm font-medium text-ink">{{ __($module) }}</h2>
                        <ul class="divide-y divide-line">
                            @foreach ($permissions as $permission)
                                @php($decision = $this->decisions[$permission->key])
                                <li class="flex flex-col gap-2 px-6 py-3 sm:flex-row sm:items-center sm:gap-4" wire:key="perm-{{ $permission->key }}">
                                    <span class="w-20 shrink-0 font-mono text-xs text-muted">{{ __('permission_type.'.$permission->type->value) }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-ink">{{ __($permission->label) }}</p>
                                        <p class="text-xs text-muted">
                                            <span @class(['font-medium', 'text-brand' => $decision->allowed, 'text-muted' => ! $decision->allowed])>{{ $decision->allowed ? __('Allowed') : __('Denied') }}</span>
                                            · {{ __('reason.'.$decision->reason->value, ['roles' => implode(', ', $decision->roles)]) }}
                                        </p>
                                    </div>
                                    @unless ($this->user->is_admin)
                                        <select
                                            class="h-9 rounded-md border border-zinc-300 bg-surface px-2 text-sm text-ink dark:border-zinc-600"
                                            aria-label="{{ __('Exception for :permission', ['permission' => __($permission->label)]) }}"
                                            wire:change="setOverride('{{ $permission->key }}', $event.target.value)"
                                        >
                                            <option value="" @selected(! isset($this->overrides[$permission->key]))>{{ __('Follow roles') }}</option>
                                            <option value="allow" @selected(($this->overrides[$permission->key] ?? null)?->value === 'allow')>{{ __('Allow for this user') }}</option>
                                            <option value="deny" @selected(($this->overrides[$permission->key] ?? null)?->value === 'deny')>{{ __('Deny for this user') }}</option>
                                        </select>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @else
            <section class="rounded-lg border border-line bg-surface px-6 py-2">
                <x-ui.audit-list :entries="$this->history" />
            </section>
        @endif
    </div>
</div>
