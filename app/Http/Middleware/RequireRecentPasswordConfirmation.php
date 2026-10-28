<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Password confirmation that always redirects, so Livewire also blocks component
 * updates that ask for JSON (Livewire only aborts on redirects) — A-01.
 */
final class RequireRecentPasswordConfirmation extends RequirePassword
{
    public function __construct(ResponseFactory $responseFactory, UrlGenerator $urlGenerator, Repository $config)
    {
        parent::__construct($responseFactory, $urlGenerator, $config->integer('auth.password_timeout', 10800));
    }

    /**
     * The timeout always comes from config('auth.password_timeout'); per-route overrides are not supported.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle($request, Closure $next, $redirectToRoute = null, $passwordTimeoutSeconds = null): Response
    {
        if ($this->shouldConfirmPassword($request)) {
            return $this->responseFactory->redirectGuest(
                $this->urlGenerator->route($redirectToRoute ?: 'password.confirm')
            );
        }

        return $next($request);
    }
}
