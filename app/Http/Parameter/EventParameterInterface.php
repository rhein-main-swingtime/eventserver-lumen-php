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
    ];

    public const VALIDATIONS = [
        self::PARAMETER_CALENDAR    => 'array',
        self::PARAMETER_CATEGORY    => 'array',
        self::PARAMETER_CITY        => 'array',
        self::PARAMETER_FROM        => 'date|',
        self::PARAMETER_LIMIT       => 'integer',
        self::PARAMETER_SKIP        => 'integer',
        self::PARAMETER_TO          => 'date|after:from',
    ];
}