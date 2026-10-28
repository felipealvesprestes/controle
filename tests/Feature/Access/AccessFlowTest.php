<?php

use App\Application\Access\SaveRole;
use App\Domain\Access\PermissionCatalog;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Finder\Finder;

function invitedUser(): array
{
    $user = User::factory()->create(['email' => 'ana@example.test', 'email_verified_at' => null, 'invited_at' => now()]);

    return [$user, Password::broker('invites')->createToken($user)];
}

describe('invitation — F-01', function () {
    it('shows the form and sets the password once', function () {
        [$user, $token] = invitedUser();

        $this->get(route('invitation.show', ['token' => $token, 'email' => $user->email]))->assertOk()->assertSee($user->email);

        $payload = ['token' => $token, 'email' => 'ANA@example.test', 'password' => 'a-new-password', 'password_confirmation' => 'a-new-password'];

        $this->post(route('invitation.store'), $payload)->assertRedirect(route('login'))->assertSessionHas('status');
        expect(Hash::check('a-new-password', (string) $user->fresh()?->password))->toBeTrue()
            ->and($user->fresh()?->email_verified_at)->not->toBeNull();

        $this->post(route('invitation.store'), $payload)->assertSessionHasErrors('email');
    });

    it('does not accept a password reset token on the invitation route', function () {
        [$user] = invitedUser();
        $resetToken = Password::broker('users')->createToken($user);

        $this->post(route('invitation.store'), ['token' => $resetToken, 'email' => $user->email, 'password' => 'a-new-password', 'password_confirmation' => 'a-new-password'])
            ->assertSessionHasErrors('email');
    });

    it('does not activate a deactivated account', function () {
        [$user, $token] = invitedUser();
        $user->forceFill(['active' => false])->save();

        $this->post(route('invitation.store'), ['token' => $token, 'email' => $user->email, 'password' => 'a-new-password', 'password_confirmation' => 'a-new-password'])
            ->assertSessionHasErrors('email');
        expect(Hash::check('a-new-password', (string) $user->fresh()?->password))->toBeFalse();
    });

    it('builds the invitation link from APP_URL — A-04', function () {
        config(['app.url' => 'https://horas.example.test']);
        [$user] = invitedUser();

        $mail = (new InvitationNotification('tok'))->toMail($user);

        expect((new InvitationNotification('tok'))->via($user))->toBe(['mail'])
            ->and((string) $mail->actionUrl)->toStartWith('https://horas.example.test/invitation/tok');
    });
});

describe('active accounts only — RN-19', function () {
    it('refuses to sign in an inactive or unknown user with the same message', function () {
        $inactive = User::factory()->inactive()->create();

        $this->post(route('login.store'), ['email' => $inactive->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => 'nobody@example.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('logs out a user deactivated during their session', function () {
        $user = User::factory()->admin()->create();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
        $this->get(route('dashboard'))->assertOk();

        $user->forceFill(['active' => false])->save();
        app('auth')->forgetGuards();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    });

    it('records the last access on login', function () {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
        event(new Login('web', new GenericUser(['id' => 0]), false));

        expect($user->fresh()?->last_login_at)->not->toBeNull();
    });
});

describe('authorization — RN-18', function () {
    it('sends each user to the first screen they may open', function () {
        $this->actingAs(User::factory()->admin()->create())->get(route('home.redirect'))->assertRedirect(route('dashboard'));
        $this->actingAs(User::factory()->create())->get(route('home.redirect'))->assertRedirect(route('profile.edit'));
    });

    it('returns 403 to regular users on every protected page', function (string $route) {
        $admin = User::factory()->admin()->create();
        $roleId = app(SaveRole::class)->handle($admin->id, null, 'Any', '', []);

        $this->actingAs(User::factory()->create())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route($route, ['user' => $admin->id, 'role' => $roleId]))
            ->assertForbidden();
    })->with(['dashboard', 'admin.users.index', 'admin.users.show', 'admin.roles.index', 'admin.roles.show']);

    it('answers only catalog abilities and access.manage', function () {
        $admin = User::factory()->admin()->create();

        expect(Gate::forUser($admin)->allows('access.manage'))->toBeTrue()
            ->and(Gate::forUser(User::factory()->create())->allows('access.manage'))->toBeFalse()
            ->and(Gate::forUser($admin)->allows('unknown.ability'))->toBeFalse();
    });

    it('masks amounts and hides blocks the user may not see — F-17', function () {
        $admin = User::factory()->admin()->create();
        $roleId = app(SaveRole::class)->handle($admin->id, null, 'Viewer', '', ['dashboard.page']);
        $viewer = User::factory()->create();
        $viewer->roles()->attach($roleId);

        $this->actingAs($viewer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('•••')
            ->assertDontSee('R$ 1.335,00')
            ->assertDontSee(__('Initial setup'));
    });

    it('uses only catalog permissions in views and routes', function () {
        $root = dirname(__DIR__, 3);
        $known = [...app(PermissionCatalog::class)->keys(), 'access.manage'];
        $used = [];

        foreach (Finder::create()->files()->in([$root.'/resources/views', $root.'/routes', $root.'/app'])->name('*.php') as $file) {
            preg_match_all('/@can\(\s*\'([^\']+)\'|can:([a-z_.]+)|->can\(\s*\'([^\']+)\'|permission="([^"]+)"/', $file->getContents(), $matches);
            $used = [...$used, ...array_filter([...$matches[1], ...$matches[2], ...$matches[3], ...$matches[4]])];
        }

        expect(array_values(array_diff(array_unique($used), $known)))->toBe([]);
    });
});
