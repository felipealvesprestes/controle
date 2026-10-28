<div>
    <x-ui.page-header
        :title="__('Users')"
        :description="trans_choice(':count user|:count users', $this->users->count())"
        :breadcrumbs="[config('app.name') => route('home.redirect'), __('Administration') => null, __('Users') => null]"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="user-plus" wire:click="$set('inviting', true)" data-test="invite-user-button">{{ __('Invite user') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="p-4 sm:p-8">
        <div class="overflow-hidden rounded-lg border border-line bg-surface">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-muted">
                    <tr class="border-b border-line">
                        <th class="px-6 py-3 font-normal">{{ __('Name') }}</th>
                        <th class="hidden px-6 py-3 font-normal md:table-cell">{{ __('Roles') }}</th>
                        <th class="px-6 py-3 font-normal">{{ __('Status') }}</th>
                        <th class="hidden px-6 py-3 text-right font-normal lg:table-cell">{{ __('Last access') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($this->users as $user)
                        <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-white/5" wire:key="user-{{ $user->id }}">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="font-medium text-brand hover:underline">{{ $user->name }}</a>
                                <p class="text-muted">{{ $user->email }}</p>
                            </td>
                            <td class="hidden px-6 py-3 text-ink md:table-cell">
                                {{ $user->is_admin ? __('Administrator') : ($user->roles->pluck('name')->join(', ') ?: '—') }}
                            </td>
                            <td class="px-6 py-3">
                                @if (! $user->active)
                                    <span class="inline-flex items-center gap-1.5 text-muted"><span class="size-2 rounded-full bg-zinc-400"></span>{{ __('Inactive') }}</span>
                                @elseif ($user->invited_at && ! $user->last_login_at)
                                    <span class="inline-flex items-center gap-1.5 text-ink"><span class="size-2 rounded-full border-2 border-brand"></span>{{ __('Invited') }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-ink"><span class="size-2 rounded-full bg-positive"></span>{{ __('Active') }}</span>
                                @endif
                            </td>
                            <td class="num hidden px-6 py-3 text-right text-muted lg:table-cell">
                                {{ $user->last_login_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <x-ui.side-panel :title="__('Invite user')" open="inviting">
        <form wire:submit="invite" class="flex flex-1 flex-col">
            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                <flux:input wire:model="name" :label="__('Name')" required autocomplete="off" />
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="off" />

                <flux:checkbox.group wire:model="roleIds" :label="__('Roles')">
                    @forelse ($this->roles as $role)
                        <flux:checkbox :value="$role->id" :label="$role->name" :description="$role->description" />
                    @empty
                        <p class="text-sm text-muted">{{ __('No roles yet. The user will only see their own profile until you give them one.') }}</p>
                    @endforelse
                </flux:checkbox.group>
                <flux:error name="roles" />

                <p class="text-sm text-muted">{{ __('They receive a link that works once and expires in 72 hours.') }}</p>
            </div>
            <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
                <flux:button type="button" x-on:click="open = false">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" data-test="send-invitation-button">{{ __('Send invitation') }}</flux:button>
            </div>
        </form>
    </x-ui.side-panel>
</div>
