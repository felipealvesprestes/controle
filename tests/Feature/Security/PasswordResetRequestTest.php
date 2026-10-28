<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

it('builds the reset link from APP_URL, not from the request host — A-04', function () {
    Notification::fake();
    config(['app.url' => 'https://horas.example.test']);
    $user = User::factory()->create();

    $this->withServerVariables(['HTTP_HOST' => 'attacker.test'])
        ->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        return str_starts_with((string) $notification->toMail($user)->actionUrl, 'https://horas.example.test/reset-password/');
    });
});

it('answers the same way for unknown and known emails — A-05', function () {
    Notification::fake();
    $user = User::factory()->create();

    $unknown = $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'nobody@example.test']);
    $known = $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email]);
    $throttled = $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email]);

    foreach ([$unknown, $known, $throttled] as $response) {
        $response->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('passwords.sent'));
    }
});

it('answers JSON requests the same way for unknown emails — A-05', function () {
    $this->postJson(route('password.email'), ['email' => 'nobody@example.test'])
        ->assertOk()
        ->assertJson(['message' => __('passwords.sent')]);
});

it('limits password reset requests per IP — A-05', function () {
    Notification::fake();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('password.email'), ['email' => "nobody{$attempt}@example.test"])->assertRedirect();
    }

    $this->post(route('password.email'), ['email' => 'nobody6@example.test'])->assertTooManyRequests();
});

it('refuses to build a reset link for something that cannot reset a password — A-04', function () {
    (new ResetPassword('token'))->toMail(new stdClass);
})->throws(InvalidArgumentException::class);
