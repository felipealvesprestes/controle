<?php

namespace App\Domain\Provider;

use RuntimeException;

/**
 * Provider settings that break a rule; the message is a translation key.
 */
final class ProviderRuleViolation extends RuntimeException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
