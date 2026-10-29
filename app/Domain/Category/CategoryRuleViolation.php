<?php

namespace App\Domain\Category;

use RuntimeException;

/**
 * A category change that breaks a rule; the message is a translation key.
 */
final class CategoryRuleViolation extends RuntimeException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
