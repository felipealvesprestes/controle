<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\UniformPasswordResetLinkResponse;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, UniformPasswordResetLinkResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configurePasswordResetLinks();
    }

    /**
     * Build reset links from APP_URL so a forged Host header cannot redirect the token (A-04).
     */
    private function configurePasswordResetLinks(): void
    {
        ResetPassword::createUrlUsing(function (mixed $notifiable, string $token): string {
            if (! $notifiable instanceof CanResetPassword) {
                throw new InvalidArgumentException('Password reset links can only be built for resettable users.');
            }

            return rtrim(Config::string('app.url'), '/').route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], absolute: false);
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Only active accounts sign in (RN-19). The hash is always checked so timing does not reveal accounts.
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where('email', $request->string(Fortify::username())->lower()->toString())->first();
            $valid = Hash::check($request->string('password')->toString(), $user->password ?? Hash::make(Str::random(32)));

            return $valid && $user !== null && $user->active ? $user : null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate($request->string(Fortify::username())->lower()->append('|', (string) $request->ip())->toString());

            return Limit::perMinute(5)->by($throttleKey);
        });

    }
}
