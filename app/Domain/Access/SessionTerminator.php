<?php

namespace App\Domain\Access;

/**
 * Ends every open session of a user (RN-19).
 */
interface SessionTerminator
{
    public function endAll(int $userId): void;
}
