<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="space-y-4">
    <p class="text-sm text-muted">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}</p>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button data-test="delete-user-button" class="!border-negative/40 !text-negative hover:!bg-negative-soft">
            {{ __('Delete account') }}
        </flux:button>
    </flux:modal.trigger>

    <livewire:pages::settings.delete-user-modal />
</section>
