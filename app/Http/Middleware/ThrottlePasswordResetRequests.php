<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limits password reset requests per IP; Fortify registers these routes without a limiter — A-05.
 */
final class ThrottlePasswordResetRequests
{
    private const int MAX_ATTEMPTS = 5;

    private const int DECAY_SECONDS = 60;

    public function __construct(private readonly RateLimiter $limiter) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->routeIs('password.email', 'password.update')) {
            return $next($request);
        }

        $key = 'password-reset|'.$request->ip();

        if ($this->limiter->tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw new ThrottleRequestsException(headers: ['Retry-After' => $this->limiter->availableIn($key)]);
        }

        $this->limiter->hit($key, self::DECAY_SECONDS);

        return $next($request);
    }
}
