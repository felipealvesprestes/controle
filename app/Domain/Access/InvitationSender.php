<?php

namespace App\Domain\Access;

/**
 * Sends the single-use link a new user follows to set their password (F-01).
 */
interface InvitationSender
{
    public function send(int $userId): void;
}
