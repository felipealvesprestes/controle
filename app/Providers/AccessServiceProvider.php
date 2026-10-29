<?php

namespace App\Providers;

use App\Domain\Access\AccessRepository;
use App\Domain\Access\AuditTrail;
use App\Domain\Access\Authorizer;
use App\Domain\Access\InvitationSender;
use App\Domain\Access\PermissionCatalog;
use App\Domain\Access\PermissionProvider;
use App\Domain\Access\SessionTerminator;
use App\Domain\Category\CategoryPermissions;
use App\Domain\Client\ClientPermissions;
use App\Domain\Dashboard\DashboardPermissions;
use App\Domain\Provider\ProviderPermissions;
use App\Infrastructure\Access\CachedAuthorizer;
use App\Infrastructure\Access\DatabaseAuditTrail;
use App\Infrastructure\Access\DatabaseSessionTerminator;
use App\Infrastructure\Access\EloquentAccessRepository;
use App\Infrastructure\Access\PasswordBrokerInvitationSender;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Wires users and granular permissions (F-17, ADR-0007).
 */
class AccessServiceProvider extends ServiceProvider
{
    /**
     * Modules that declare permissions. A new module adds its provider here.
     *
     * @var list<class-string<PermissionProvider>>
     */
    public const array PERMISSION_PROVIDERS = [
        DashboardPermissions::class,
        ProviderPermissions::class,
        ClientPermissions::class,
        CategoryPermissions::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PermissionCatalog::class, fn (): PermissionCatalog => new PermissionCatalog(
            array_map(fn (string $provider) => $this->app->make($provider), self::PERMISSION_PROVIDERS),
        ));

        $this->app->bind(AccessRepository::class, EloquentAccessRepository::class);
        $this->app->bind(AuditTrail::class, DatabaseAuditTrail::class);
        $this->app->bind(InvitationSender::class, PasswordBrokerInvitationSender::class);
        $this->app->bind(SessionTerminator::class, DatabaseSessionTerminator::class);
        $this->app->scoped(Authorizer::class, CachedAuthorizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Catalog permissions are answered by the resolver; any other ability falls through (RN-18).
        Gate::before(function (User $user, string $ability): ?bool {
            if (! $this->app->make(PermissionCatalog::class)->has($ability)) {
                return null;
            }

            return $this->app->make(Authorizer::class)->allows($user->id, $ability);
        });

        Gate::define('access.manage', fn (User $user): bool => $user->is_admin && $user->active);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }
}
