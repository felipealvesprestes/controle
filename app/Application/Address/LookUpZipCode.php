<?php

namespace App\Application\Address;

use App\Domain\Access\AccessDenied;
use App\Domain\Access\Authorizer;
use App\Domain\Address\AddressLookup;
use App\Domain\Address\AddressSuggestion;
use App\Domain\Address\ZipCodeLookupRefused;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Fills an address form from its CEP, for whoever may edit that form (SEC-02, SEC-30).
 */
final class LookUpZipCode
{
    /** Lookups per user per minute: enough for typing, too few to use the system as a proxy. */
    public const int PER_MINUTE = 20;

    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly AddressLookup $lookup,
    ) {}

    /**
     * @param  string  $permission  The edit permission of the form being filled.
     */
    public function handle(int $actorId, string $permission, string $zipCode): ?AddressSuggestion
    {
        if (! $this->authorizer->allows($actorId, $permission)) {
            throw new AccessDenied('You may not fill this address.');
        }

        $digits = (string) preg_replace('/\D/', '', $zipCode);

        if (strlen($digits) !== 8) {
            throw new ZipCodeLookupRefused('Enter the 8 digits of the CEP.');
        }

        $key = 'zip-code-lookup:'.$actorId;

        if (RateLimiter::tooManyAttempts($key, self::PER_MINUTE)) {
            throw new ZipCodeLookupRefused('Too many CEP lookups. Wait a minute and fill in the address.');
        }

        RateLimiter::hit($key);

        return $this->lookup->find($digits);
    }
}
