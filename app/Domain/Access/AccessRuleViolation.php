<?php

namespace App\Domain\Access;

use RuntimeException;

/**
 * An access change that breaks a business rule; the message is a translation key.
 */
final class AccessRuleViolation extends RuntimeException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
