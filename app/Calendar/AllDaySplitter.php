<?php

namespace App\Calendar;

use App\Models\EventInstance;
use Carbon\CarbonImmutable;

/**
 * Splits all-day events spanning multiple days into one segment per day.
 */
class AllDaySplitter
{
    /**
     * @param string $start Start of the event (DB format, midnight)
     * @param string $end   Exclusive end of the event (DB format, midnight)
     * @return array<int, array{start: string, end: string, day_number: ?int, day_count: ?int}>
     */
    public static function split(string $start, string $end): array
    {
        $startDay = CarbonImmutable::parse($start)->startOfDay();
        $endDay = CarbonImmutable::parse($end)->startOfDay();
        $dayCount = (int) $startDay->diffInDays($endDay);

        if ($dayCount <= 1) {
            return [[
                'start'         => $start,
                'end'           => $end,
                'day_number'    => null,
                'day_count'     => null,
            ]];
        }

        $segments = [];
        for ($i = 0; $i < $dayCount; $i++) {
            $day = $startDay->addDays($i);
            $segments[] = [
                'start'         => $day->format(EventInstance::DATE_TIME_FORMAT_DB),
                'end'           => $day->addDay()->format(EventInstance::DATE_TIME_FORMAT_DB),
                'day_number'    => $i + 1,
                'day_count'     => $dayCount,
            ];
        }

        return $segments;
    }
}
