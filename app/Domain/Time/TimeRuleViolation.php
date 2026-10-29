<?php

namespace App\Domain\Time;

use RuntimeException;

/**
 * A time entry or timer change that breaks a rule; the message is a translation key.
 */
final class TimeRuleViolation extends RuntimeException
{
    /**
     * @param  array<string, string>  $replace  Values for the placeholders of the message.
     * @param  int|null  $conflictId  The entry in the way, for an overlap (RN-03).
     */
    public function __construct(
        public readonly string $field,
        string $message,
        public readonly array $replace = [],
        public readonly ?int $conflictId = null,
    ) {
        parent::__construct($message);
    }
}
