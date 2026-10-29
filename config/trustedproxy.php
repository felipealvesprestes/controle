<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated addresses or CIDRs of the reverse proxies in front of the
    | app (the cloudflared tunnel in production, ADR-0010). Only these peers may
    | set X-Forwarded-For, so per-client rate limits see the real client IP.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
