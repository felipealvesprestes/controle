<?php

namespace App\Providers;

use App\Domain\Address\AddressLookup;
use App\Domain\Provider\ProviderProfileRepository;
use App\Http\Middleware\RequireRecentPasswordConfirmation;
use App\Infrastructure\Address\ViaCepAddressLookup;
use App\Infrastructure\Provider\EloquentProviderProfileRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProviderProfileRepository::class, EloquentProviderProfileRepository::class);
        $this->app->bind(AddressLookup::class, ViaCepAddressLookup::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureLivewire();

        // No <link rel="preload"> tags: wire:navigate re-inserts them on every visit and the browser downloads the CSS again.
        Vite::usePreloadTagAttributes(false);
    }

    /**
     * Re-apply step-up middleware on every Livewire update, not only on the page load (A-01).
     */
    protected function configureLivewire(): void
    {
        Livewire::addPersistentMiddleware([RequireRecentPasswordConfirmation::class]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
