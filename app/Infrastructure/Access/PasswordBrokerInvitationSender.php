<?php

namespace App\Infrastructure\Access;

use App\Domain\Access\InvitationSender;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Facades\Password;

/**
 * Issues a single-use token from the "invites" broker and emails the link (F-01).
 */
final class PasswordBrokerInvitationSender implements InvitationSender
{
    public function send(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $token = Password::broker('invites')->createToken($user);

        $user->notify(new InvitationNotification($token));
    }
}
