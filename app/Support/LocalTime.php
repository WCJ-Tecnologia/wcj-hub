<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The database and the app run in UTC; people read local time. Every date
 * that reaches a screen, and every "from day X to day Y" filter, passes through
 * here. Filtering a UTC column by the local day without it loses whatever
 * happened after 21h. See banco.md §3.
 */
final class LocalTime
{
    public static function zone(): string
    {
        return (string) config('app.display_timezone');
    }

    public static function format(?CarbonInterface $moment, string $format = 'd/m/Y H:i'): ?string
    {
        return $moment?->copy()->setTimezone(self::zone())->format($format);
    }

    /**
     * First instant, in UTC, of a local calendar day ("2026-09-24").
     */
    public static function startOfDay(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, self::zone())->startOfDay()->utc();
    }

    /**
     * Last instant, in UTC, of a local calendar day ("2026-09-24").
     */
    public static function endOfDay(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, self::zone())->endOfDay()->utc();
    }
}
