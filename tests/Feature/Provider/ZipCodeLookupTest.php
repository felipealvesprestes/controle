<?php

use App\Application\Access\SaveRole;
use App\Application\Address\LookUpZipCode;
use App\Domain\Access\AccessDenied;
use App\Domain\Address\AddressLookup;
use App\Domain\Address\ZipCodeLookupRefused;
use App\Livewire\Settings\CompanyDetailsSettings;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

const CANOAS = ['cep' => '92010-000', 'logradouro' => 'Rua Exemplo', 'bairro' => 'Centro', 'localidade' => 'Canoas', 'uf' => 'RS'];

beforeEach(fn () => $this->actingAs(User::factory()->admin()->create()));

it('fills street, district, city and state from the CEP and keeps number and complement', function () {
    Http::fake(['viacep.com.br/ws/92010000/json/' => Http::response(CANOAS)]);

    Livewire::test(CompanyDetailsSettings::class)
        ->set('number', '100')
        ->set('complement', 'Sala 2')
        ->set('zipCode', '92010000')
        ->assertHasNoErrors()
        ->assertSet('zipCode', '92010-000')
        ->assertSet('street', 'Rua Exemplo')
        ->assertSet('district', 'Centro')
        ->assertSet('city', 'Canoas')
        ->assertSet('state', 'RS')
        ->assertSet('number', '100')
        ->assertSet('complement', 'Sala 2');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://viacep.com.br/ws/92010000/json/');
});

it('keeps what was typed when a city-wide CEP has no street or district', function () {
    Http::fake(['viacep.com.br/*' => Http::response(['logradouro' => '', 'bairro' => '', 'localidade' => 'Nova Santa Rita', 'uf' => 'RS'])]);

    Livewire::test(CompanyDetailsSettings::class)
        ->set('street', 'Rua Típica')
        ->set('zipCode', '92480-000')
        ->assertSet('street', 'Rua Típica')
        ->assertSet('city', 'Nova Santa Rita');
});

it('looks a CEP up only once a month, from the cache', function () {
    Http::fake(['viacep.com.br/*' => Http::response(CANOAS)]);

    Livewire::test(CompanyDetailsSettings::class)->set('zipCode', '92010-000');
    Livewire::test(CompanyDetailsSettings::class)->set('zipCode', '92010-000')->assertSet('city', 'Canoas');

    Http::assertSentCount(1);
});

it('waits for the 8 digits before looking up', function () {
    Livewire::test(CompanyDetailsSettings::class)->set('zipCode', '9201')->assertHasNoErrors();

    Http::assertNothingSent();
});

it('says so next to the CEP when it does not exist or the directory fails', function (Closure $response, string $message) {
    Http::fake(['viacep.com.br/*' => $response]);

    Livewire::test(CompanyDetailsSettings::class)
        ->set('city', 'Canoas')
        ->set('zipCode', '99999-999')
        ->assertHasErrors(['zipCode' => __($message)])
        ->assertSet('city', 'Canoas');
})->with([
    'unknown CEP' => [fn () => Http::response(['erro' => 'true']), 'CEP not found. Check it or fill in the address.'],
    'server error' => [fn () => Http::response('', 500), 'The CEP lookup is unavailable right now. Fill in the address.'],
    'not JSON' => [fn () => Http::response('<html>'), 'The CEP lookup is unavailable right now. Fill in the address.'],
    'redirect' => [fn () => Http::response('', 302, ['Location' => 'https://example.test/']), 'The CEP lookup is unavailable right now. Fill in the address.'],
    'unknown state' => [fn () => Http::response([...CANOAS, 'uf' => 'XX']), 'The CEP lookup is unavailable right now. Fill in the address.'],
    'no city' => [fn () => Http::response([...CANOAS, 'localidade' => ' ']), 'The CEP lookup is unavailable right now. Fill in the address.'],
    'timeout' => [fn () => fn () => throw new ConnectionException('timed out'), 'The CEP lookup is unavailable right now. Fill in the address.'],
]);

it('trusts nothing in the answer: cuts long text and ignores non-text values', function () {
    Http::fake(['viacep.com.br/*' => Http::response([...CANOAS, 'logradouro' => str_repeat('a', 400), 'bairro' => ['x'], 'uf' => 'rs'])]);

    $component = Livewire::test(CompanyDetailsSettings::class)->set('zipCode', '92010-000')->assertSet('state', 'RS');

    expect(mb_strlen($component->get('street')))->toBe(255)
        ->and($component->get('district'))->toBe('');
});

it('limits lookups per user — SEC-30', function () {
    Http::fake(['viacep.com.br/*' => Http::response(['erro' => 'true'])]);
    $lookUp = app(LookUpZipCode::class);

    foreach (range(1, LookUpZipCode::PER_MINUTE) as $attempt) {
        $lookUp->handle((int) auth()->id(), 'settings.company.edit', sprintf('%08d', 10000000 + $attempt));
    }

    Livewire::test(CompanyDetailsSettings::class)
        ->set('zipCode', '92010-000')
        ->assertHasErrors(['zipCode' => __('Too many CEP lookups. Wait a minute and fill in the address.')]);

    Http::assertSentCount(LookUpZipCode::PER_MINUTE);
});

it('does not look up for someone who cannot edit the address — SEC-02', function () {
    $admin = User::factory()->admin()->create();
    $reader = User::factory()->create();
    $reader->roles()->attach(app(SaveRole::class)->handle($admin->id, null, 'Reader', '', ['settings.company.page']));
    $this->actingAs($reader);

    Livewire::test(CompanyDetailsSettings::class)->set('zipCode', '92010-000')->assertSet('city', '');

    expect(fn () => app(LookUpZipCode::class)->handle($reader->id, 'settings.company.edit', '92010000'))->toThrow(AccessDenied::class);
    Http::assertNothingSent();
});

it('refuses anything but 8 digits before reaching the directory', function () {
    app(LookUpZipCode::class)->handle((int) auth()->id(), 'settings.company.edit', '../../x');
})->throws(ZipCodeLookupRefused::class, 'Enter the 8 digits of the CEP.');

it('refuses to build a URL from anything but 8 digits — SSRF', function () {
    app(AddressLookup::class)->find('1234567/');
})->throws(InvalidArgumentException::class);
