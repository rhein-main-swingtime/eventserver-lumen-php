<?php
declare(strict_types=1);

namespace App\CalendarAdapter;

interface AdapterParameterInterface
{
    public const LIMIT = 'limit';   // integer
    public const FROM = 'from';     // date, eg. 2020-01-01
    public const TO = 'to';         // date, eg. 2020-01-01
    public const PAGE = 'page';     // arbitrary
}
