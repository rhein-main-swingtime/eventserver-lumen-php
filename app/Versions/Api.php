<?php

namespace App\Versions;

class Api
{
    public const v1 = 'v1';
    public const v2 = 'v2';
    public const v3 = 'v3';

    private const availableVersions = [
        self::v1,
        self::v2,
        self::v3
    ];

    private const PARAMETER_VERSION = '{version}';

    public static function VersionParam(bool $optional = false) {
        if ($optional) {
            return '[/' . self::PARAMETER_VERSION . ']';
        }
        return '/' . self::PARAMETER_VERSION;
    }
}
