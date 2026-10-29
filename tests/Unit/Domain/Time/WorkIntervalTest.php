<?php

use App\Domain\Time\TimeRuleViolation;
use App\Domain\Time\WorkInterval;

function at(string $moment): DateTimeImmutable
{
    return new DateTimeImmutable($moment, new DateTimeZone('America/Sao_Paulo'));
}

it('counts the minutes across midnight — RN-02', function () {
    expect(WorkInterval::between(at('2026-10-28 23:00'), at('2026-10-29 01:00'))->minutes()->value)->toBe(120);
});

it('drops the seconds of both ends before counting — RN-02', function () {
    $interval = WorkInterval::between(at('2026-10-28 09:00:59'), at('2026-10-28 09:45:01'));

    expect($interval->minutes()->value)->toBe(45)
        ->and($interval->start->format('H:i:s'))->toBe('09:00:00');
});

it('refuses an end that is not after the start — RN-02', function (string $end) {
    WorkInterval::between(at('2026-10-28 10:00'), at($end));
})->with(['2026-10-28 10:00:30', '2026-10-28 09:00'])->throws(TimeRuleViolation::class, 'The end must be after the start.');

it('refuses more than 12 hours and accepts exactly 12 — RN-02', function () {
    expect(WorkInterval::between(at('2026-10-28 08:00'), at('2026-10-28 20:00'))->minutes()->value)->toBe(720)
        ->and(fn () => WorkInterval::between(at('2026-10-28 08:00'), at('2026-10-28 20:01')))->toThrow(TimeRuleViolation::class, 'An entry cannot last more than 12 hours.');
});

it('asks for confirmation only above 4 hours — RN-02', function () {
    expect(WorkInterval::between(at('2026-10-28 08:00'), at('2026-10-28 12:00'))->needsConfirmation())->toBeFalse()
        ->and(WorkInterval::between(at('2026-10-28 08:00'), at('2026-10-28 12:01'))->needsConfirmation())->toBeTrue();
});
