<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-ground font-sans text-ink">
        <div class="flex min-h-svh flex-col items-center justify-center px-4 py-10">
            <div class="w-full max-w-[420px] rounded-lg border border-line bg-surface px-6 py-8 sm:px-10 sm:py-10">
                <x-app-logo :href="route('home')" wire:navigate class="mb-8" />

                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-muted">{{ __('Hours, billing and invoices') }}</p>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
