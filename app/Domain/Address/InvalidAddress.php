<?php

namespace App\Domain\Address;

use InvalidArgumentException;

/**
 * An address part that breaks a rule; the message is a translation key.
 */
final class InvalidAddress extends InvalidArgumentException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
