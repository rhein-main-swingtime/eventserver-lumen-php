<?php

use App\Calendar\AllDaySplitter;

class AllDaySplitterTest extends TestCase
{
    public function testSingleDayIsNotSplit(): void
    {
        $segments = AllDaySplitter::split('2026-10-01 00:00:00', '2026-10-02 00:00:00');

        $this->assertEquals([[
            'start'         => '2026-10-01 00:00:00',
            'end'           => '2026-10-02 00:00:00',
            'day_number'    => null,
            'day_count'     => null,
        ]], $segments);
    }

    public function testMultiDayIsSplitIntoDays(): void
    {
        // Google delivers the end date exclusive: 01.10. - 03.10. ends on 04.10.
        $segments = AllDaySplitter::split('2026-10-01 00:00:00', '2026-10-04 00:00:00');

        $this->assertEquals([
            [
                'start'         => '2026-10-01 00:00:00',
                'end'           => '2026-10-02 00:00:00',
                'day_number'    => 1,
                'day_count'     => 3,
            ],
            [
                'start'         => '2026-10-02 00:00:00',
                'end'           => '2026-10-03 00:00:00',
                'day_number'    => 2,
                'day_count'     => 3,
            ],
            [
                'start'         => '2026-10-03 00:00:00',
                'end'           => '2026-10-04 00:00:00',
                'day_number'    => 3,
                'day_count'     => 3,
            ],
        ], $segments);
    }

    public function testEndNotAfterStartIsNotSplit(): void
    {
        $segments = AllDaySplitter::split('2026-10-01 00:00:00', '2026-10-01 00:00:00');

        $this->assertCount(1, $segments);
        $this->assertNull($segments[0]['day_number']);
        $this->assertNull($segments[0]['day_count']);
    }

    /**
     * @dataProvider dstProvider
     */
    public function testSplitAcrossDstChange(string $timezone, string $start, string $end, array $expected): void
    {
        $default = date_default_timezone_get();
        date_default_timezone_set($timezone);
        try {
            $segments = AllDaySplitter::split($start, $end);
        } finally {
            date_default_timezone_set($default);
        }

        $this->assertEquals($expected, array_map(
            fn ($segment) => [$segment['start'], $segment['end']],
            $segments
        ));
    }

    public function dstProvider(): array
    {
        return [
            // DST starts in Europe on 29.03.2026 at 02:00
            'europe spring' => ['Europe/Berlin', '2026-03-28 00:00:00', '2026-03-31 00:00:00', [
                ['2026-03-28 00:00:00', '2026-03-29 00:00:00'],
                ['2026-03-29 00:00:00', '2026-03-30 00:00:00'],
                ['2026-03-30 00:00:00', '2026-03-31 00:00:00'],
            ]],
            // DST ends in Europe on 25.10.2026 at 03:00
            'europe fall' => ['Europe/Berlin', '2026-10-24 00:00:00', '2026-10-27 00:00:00', [
                ['2026-10-24 00:00:00', '2026-10-25 00:00:00'],
                ['2026-10-25 00:00:00', '2026-10-26 00:00:00'],
                ['2026-10-26 00:00:00', '2026-10-27 00:00:00'],
            ]],
            // DST starts in Chile on 06.09.2026 at midnight, 00:00 does not exist
            'midnight gap' => ['America/Santiago', '2026-09-05 00:00:00', '2026-09-08 00:00:00', [
                ['2026-09-05 00:00:00', '2026-09-06 00:00:00'],
                ['2026-09-06 00:00:00', '2026-09-07 00:00:00'],
                ['2026-09-07 00:00:00', '2026-09-08 00:00:00'],
            ]],
        ];
    }
}
