<?php

use App\Calendar\AllDaySplitter;

class AllDaySplitterTest extends TestCase
{
    public function testSingleDayIsNotSplit()
    {
        $segments = AllDaySplitter::split('2026-10-01 00:00:00', '2026-10-02 00:00:00');

        $this->assertEquals([[
            'start'         => '2026-10-01 00:00:00',
            'end'           => '2026-10-02 00:00:00',
            'day_number'    => null,
            'day_count'     => null,
        ]], $segments);
    }

    public function testMultiDayIsSplitIntoDays()
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

    public function testSplitAcrossDstChange()
    {
        // DST ends in Europe on 25.10.2026
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
        try {
            $segments = AllDaySplitter::split('2026-10-24 00:00:00', '2026-10-27 00:00:00');
        } finally {
            date_default_timezone_set($timezone);
        }

        $this->assertCount(3, $segments);
        $this->assertEquals('2026-10-25 00:00:00', $segments[1]['start']);
        $this->assertEquals('2026-10-26 00:00:00', $segments[2]['start']);
    }
}
