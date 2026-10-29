<?php

namespace App\Domain\Client;

use RuntimeException;

/**
 * A client change that breaks a rule; the message is a translation key.
 */
final class ClientRuleViolation extends RuntimeException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
