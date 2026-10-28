<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    config(['admin.name' => 'Owner', 'admin.email' => ' Owner@Example.TEST ', 'admin.password' => '']);
});

it('creates a verified administrator from config — RN-17', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->sole();

    expect($admin->email)->toBe('owner@example.test')
        ->and($admin->name)->toBe('Owner')
        ->and($admin->is_admin)->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull();
});

it('sets an unknown random password when none is configured', function () {
    $this->seed(AdminUserSeeder::class);

    expect(Hash::check('', User::query()->sole()->password))->toBeFalse();
});

it('uses the configured password', function () {
    config(['admin.password' => 'a-strong-local-password']);

    $this->seed(AdminUserSeeder::class);

    expect(Hash::check('a-strong-local-password', User::query()->sole()->password))->toBeTrue();
});

it('is idempotent for an existing administrator', function () {
    $this->seed(AdminUserSeeder::class);
    $passwordHash = User::query()->sole()->password;

    config(['admin.password' => 'another-strong-password']);
    $this->seed(AdminUserSeeder::class);

    expect(User::query()->sole()->password)->toBe($passwordHash);
});

it('never promotes an existing regular account — RN-17', function () {
    User::factory()->create(['email' => 'owner@example.test']);

    $this->seed(AdminUserSeeder::class);
})->throws(RuntimeException::class, 'already belongs to a regular account');

it('refuses to run without an email', function () {
    config(['admin.email' => '']);

    $this->seed(AdminUserSeeder::class);
})->throws(RuntimeException::class, 'Set ADMIN_EMAIL');

it('refuses a weak password in production', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config(['admin.password' => 'weak']);

    // Called directly: db:seed would ask for confirmation in production.
    app(AdminUserSeeder::class)->run();
})->throws(ValidationException::class);

it('creates the test user only in the local environment', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse()
        ->and(User::query()->where('is_admin', true)->count())->toBe(1);

    app()->detectEnvironment(fn (): string => 'local');
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeTrue();
});

it('does not grant admin to regular users by default', function () {
    expect(User::factory()->create()->fresh()?->is_admin)->toBeFalse();
});
