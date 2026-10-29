<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Laravel\Fortify\Contracts\FailedTwoFactorLoginResponse;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest as FortifyTwoFactorLoginRequest;

/**
 * Ties the pending two-factor challenge to the password that started it: once the password changes,
 * a challenge begun with the old one no longer signs anyone in (A-03).
 */
class TwoFactorLoginRequest extends FortifyTwoFactorLoginRequest
{
    public const string SESSION_KEY = 'login.password_fingerprint';

    /**
     * A keyed digest of the stored password hash, so the session never holds the hash itself.
     */
    public static function passwordFingerprint(User $user): string
    {
        return hash_hmac('sha256', $user->getAuthPassword(), config()->string('app.key'));
    }

    public function challengedUser(): User
    {
        /** @var User $user */
        $user = parent::challengedUser();

        $fingerprint = $this->session()->get(self::SESSION_KEY);

        if (! is_string($fingerprint) || ! hash_equals($fingerprint, self::passwordFingerprint($user))) {
            $this->session()->forget(['login.id', 'login.remember', self::SESSION_KEY]);

            throw new HttpResponseException(app(FailedTwoFactorLoginResponse::class)->toResponse($this));
        }

        return $user;
    }
}
