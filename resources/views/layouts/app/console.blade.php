<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-ground font-sans text-ink" x-data @keydown.escape.window="$store.nav.open = false">
        {{-- Top bar: product, environment, appearance and account. Persisted across wire:navigate. --}}
        @persist('topbar')
        <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-line bg-surface px-3 sm:px-4">
            <button
                type="button"
                class="flex size-10 items-center justify-center rounded-full text-muted transition-colors hover:bg-zinc-100 hover:text-ink lg:hidden dark:hover:bg-white/5"
                @click="$store.nav.open = true"
                aria-label="{{ __('Open menu') }}"
            >
                <flux:icon.bars-3 variant="outline" class="size-5" />
            </button>

            <x-app-logo :href="route('home.redirect')" wire:navigate class="px-1" />

            @unless (app()->isProduction())
                <span class="hidden rounded-full border border-line px-2.5 py-0.5 text-xs text-muted sm:inline">
                    {{ __('Environment') }}: {{ app()->environment() }}
                </span>
            @endunless

            <div class="ms-auto flex items-center gap-1">
                <button
                    type="button"
                    class="flex size-10 items-center justify-center rounded-full text-muted transition-colors hover:bg-zinc-100 hover:text-ink dark:hover:bg-white/5"
                    x-on:click="$flux.dark = ! $flux.dark"
                    aria-label="{{ __('Toggle dark mode') }}"
                >
                    <flux:icon.sun variant="outline" class="size-5 dark:hidden" />
                    <flux:icon.moon variant="outline" class="hidden size-5 dark:block" />
                </button>

                <flux:dropdown position="bottom" align="end">
                    <button
                        type="button"
                        class="ms-1 flex size-9 items-center justify-center rounded-full bg-brand text-sm font-medium text-white ring-offset-2 transition hover:ring-2 hover:ring-brand/30 dark:text-zinc-950"
                        aria-label="{{ __('Account menu') }}"
                        data-test="sidebar-menu-button"
                    >
                        {{ auth()->user()->initials() }}
                    </button>

                    <flux:menu class="min-w-64">
                        <div class="px-3 py-2">
                            <p class="truncate text-sm font-medium text-ink">{{ auth()->user()->name }}</p>
                            <p class="truncate text-sm text-muted">{{ auth()->user()->email }}</p>
                        </div>
                        <flux:menu.separator />
                        <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>{{ __('Profile') }}</flux:menu.item>
                        <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate>{{ __('Security') }}</flux:menu.item>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </header>
        @endpersist

        <div class="flex">
            {{-- Scrim for the mobile drawer. --}}
            <div
                x-show="$store.nav.open"
                x-transition.opacity.duration.150ms
                class="fixed inset-0 z-30 bg-zinc-950/40 lg:hidden"
                @click="$store.nav.open = false"
                x-cloak
            ></div>

            {{-- Side navigation: a drawer below lg, a fixed column above. Persisted across wire:navigate. --}}
            @persist('sidebar')
            <aside
                class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-line bg-surface transition-transform duration-200 ease-out lg:sticky lg:top-14 lg:z-0 lg:h-[calc(100vh-3.5rem)] lg:w-64 lg:translate-x-0"
                :class="$store.nav.open && '!translate-x-0'"
                aria-label="{{ __('Main navigation') }}"
            >
                <div class="flex h-14 items-center border-b border-line px-4 lg:hidden">
                    <x-app-logo :href="route('home.redirect')" wire:navigate />
                    <button type="button" class="ms-auto flex size-10 items-center justify-center rounded-full text-muted hover:bg-zinc-100 dark:hover:bg-white/5" @click="$store.nav.open = false" aria-label="{{ __('Close menu') }}">
                        <flux:icon.x-mark variant="outline" class="size-5" />
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto py-3 pr-3">
                    @can('dashboard.menu')
                        <p class="px-6 pt-2 pb-1.5 text-sm font-medium text-ink">{{ __('Work') }}</p>
                        <x-ui.nav-item :href="route('dashboard')" icon="squares-2x2" exact>{{ __('Dashboard') }}</x-ui.nav-item>

                        <div class="my-3 border-t border-line"></div>
                    @endcan

                    @can('client.menu')
                        <p class="px-6 pt-2 pb-1.5 text-sm font-medium text-ink">{{ __('Records') }}</p>
                        <x-ui.nav-item :href="route('clients.index')" icon="briefcase">{{ __('Clients') }}</x-ui.nav-item>

                        <div class="my-3 border-t border-line"></div>
                    @endcan

                    @can('access.manage')
                        <p class="px-6 pt-2 pb-1.5 text-sm font-medium text-ink">{{ __('Administration') }}</p>
                        <x-ui.nav-item :href="route('admin.users.index')" icon="users">{{ __('Users') }}</x-ui.nav-item>
                        <x-ui.nav-item :href="route('admin.roles.index')" icon="key">{{ __('Roles') }}</x-ui.nav-item>

                        <div class="my-3 border-t border-line"></div>
                    @endcan

                    @canany(['settings.billing.menu', 'settings.company.menu'])
                        <p class="px-6 pt-2 pb-1.5 text-sm font-medium text-ink">{{ __('Settings') }}</p>
                        @can('settings.billing.menu')
                            <x-ui.nav-item :href="route('settings.billing.edit')" icon="banknotes">{{ __('Billing defaults') }}</x-ui.nav-item>
                        @endcan
                        @can('settings.company.menu')
                            <x-ui.nav-item :href="route('settings.company.edit')" icon="building-office">{{ __('Company details') }}</x-ui.nav-item>
                        @endcan

                        <div class="my-3 border-t border-line"></div>
                    @endcanany

                    <p class="px-6 pt-2 pb-1.5 text-sm font-medium text-ink">{{ __('Account') }}</p>
                    <x-ui.nav-item :href="route('profile.edit')" icon="user-circle">{{ __('Profile') }}</x-ui.nav-item>
                    <x-ui.nav-item :href="route('security.edit')" icon="shield-check">{{ __('Security') }}</x-ui.nav-item>
                    <x-ui.nav-item :href="route('appearance.edit')" icon="swatch">{{ __('Appearance') }}</x-ui.nav-item>
                </nav>

                <p class="border-t border-line px-6 py-3 text-xs text-muted">{{ config('app.name') }} v0.1</p>
            </aside>
            @endpersist

            <main class="min-w-0 flex-1">
                {{ $slot }}
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
