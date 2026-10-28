<x-layouts::auth :title="__('Set my password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Set my password')" :description="__('Choose the password you will use to sign in.')" />

        <form method="POST" action="{{ route('invitation.store') }}" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <flux:input name="email" :label="__('Email')" :value="old('email', $email)" type="email" required autocomplete="email" readonly />

            <flux:input name="password" :label="__('Password')" type="password" required autocomplete="new-password" viewable />

            <flux:input name="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" viewable />

            <flux:button type="submit" variant="primary" class="w-full" data-test="accept-invitation-button">
                {{ __('Set my password') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
