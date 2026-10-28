<x-ui.page-header
    :title="__('Settings')"
    :description="__('Manage your profile and account settings')"
    :breadcrumbs="[config('app.name') => route('home.redirect'), __('Settings') => null]"
>
    <x-slot:tabs>
        <x-ui.tab :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">{{ __('Profile') }}</x-ui.tab>
        <x-ui.tab :href="route('security.edit')" :active="request()->routeIs('security.edit')">{{ __('Security') }}</x-ui.tab>
        <x-ui.tab :href="route('appearance.edit')" :active="request()->routeIs('appearance.edit')">{{ __('Appearance') }}</x-ui.tab>
    </x-slot:tabs>
</x-ui.page-header>
