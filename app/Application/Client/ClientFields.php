<?php

namespace App\Application\Client;

use App\Domain\Client\Client;
use App\Domain\Client\ClientColor;
use App\Domain\Client\ClientRepository;
use App\Domain\Client\ClientRuleViolation;
use App\Domain\Shared\HourlyRate;
use App\Domain\Shared\Money;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Reads the client fields typed in the forms, shared by the client use cases (F-03).
 */
final class ClientFields
{
    public function __construct(private readonly ClientRepository $clients) {}

    public static function text(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * A short name no other client uses.
     */
    public function name(string $input, ?int $clientId = null): string
    {
        $name = self::text($input);

        if ($name === '') {
            throw new ClientRuleViolation('name', 'Enter a short name.');
        }

        if (mb_strlen($name) > Client::NAME_LIMIT) {
            throw new ClientRuleViolation('name', 'Too long for this field.');
        }

        if ($this->clients->nameTaken($name, $clientId)) {
            throw new ClientRuleViolation('name', 'Another client already uses this name.');
        }

        return $name;
    }

    public static function color(string $input): ClientColor
    {
        return ClientColor::tryFrom($input) ?? throw new ClientRuleViolation('color', 'Choose a colour.');
    }

    public static function rate(string $input, string $field = 'rate'): HourlyRate
    {
        try {
            return HourlyRate::of(Money::parse($input));
        } catch (InvalidArgumentException) {
            throw new ClientRuleViolation($field, 'Enter an hourly rate between 0,01 and 100.000,00, such as 60,00.');
        }
    }

    /**
     * A day typed by the date field (YYYY-MM-DD), between 2000 and 2099.
     */
    public static function day(string $input, string $field): DateTimeImmutable
    {
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', trim($input));

        if ($day === false || $day->format('Y-m-d') !== trim($input) || (int) $day->format('Y') < 2000 || (int) $day->format('Y') > 2099) {
            throw new ClientRuleViolation($field, 'Enter a valid date.');
        }

        return $day;
    }
}
