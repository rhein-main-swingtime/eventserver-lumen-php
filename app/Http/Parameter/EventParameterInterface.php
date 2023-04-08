<?php

namespace App\Http\Parameter;

interface EventParameterInterface
{
    public const PARAMETER_CALENDAR     = 'calendar';
    public const PARAMETER_CATEGORY     = 'category';
    public const PARAMETER_CITY         = 'city';
    public const PARAMETER_FROM         = 'from';
    public const PARAMETER_LIMIT        = 'limit';
    public const PARAMETER_SKIP         = 'skip';
    public const PARAMETER_TO           = 'to';
    public const PARAMETER_ID           = 'id';
    public const PARAMETER_QUERY        = 'q';
    public const PARAMETER_WEEKDAY      = 'weekday';

    public const FILTER_PARAMETERS = [
        self::PARAMETER_CALENDAR,
        self::PARAMETER_CATEGORY,
        self::PARAMETER_CITY,
    ];

    public const PARAMETERS = [
        self::PARAMETER_CALENDAR,
        self::PARAMETER_CATEGORY,
        self::PARAMETER_CITY,
        self::PARAMETER_FROM,
        self::PARAMETER_LIMIT,
        self::PARAMETER_SKIP,
        self::PARAMETER_TO,
        self::PARAMETER_WEEKDAY,
    ];

    public const VALIDATIONS = [
        self::PARAMETER_CALENDAR        => 'array',
        self::PARAMETER_CATEGORY        => 'array',
        self::PARAMETER_CITY            => 'array',
        self::PARAMETER_FROM            => 'date|',
        self::PARAMETER_LIMIT           => 'integer',
        self::PARAMETER_SKIP            => 'integer',
        self::PARAMETER_TO              => 'date|after:from',
        self::PARAMETER_QUERY           => 'string',
        self::PARAMETER_ID              => 'array',
        self::PARAMETER_ID.'.*'         => 'integer',
        self::PARAMETER_WEEKDAY         => 'array',
        self::PARAMETER_WEEKDAY.'.*'    => 'integer',
    ];
}
