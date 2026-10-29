<?php

use App\Livewire\Settings\BillingDefaultsSettings;
use App\Models\ProviderProfile;
use App\Models\User;
use Livewire\Livewire;

it('refuses a default rate that would overflow the column instead of failing with a 500 — A-11', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(BillingDefaultsSettings::class)
        ->set('rate', '999.999.999,99')
        ->call('save')
        ->assertHasErrors('rate');

    expect(ProviderProfile::query()->count())->toBe(0);
});
