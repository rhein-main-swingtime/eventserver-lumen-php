<?php
declare(strict_types=1);

namespace App\City;

class CityIdentifier
{

    public const CITY_DARMSTADT     = 'Darmstadt';
    public const CITY_ESCHBORN      = 'Eschborn';
    public const CITY_FRANKFURT     = 'Frankfurt';
    public const CITY_GIESSEN       = 'Gießen';
    public const CITY_MAINZ         = 'Mainz';
    public const CITY_OFFENBACH     = 'Offenbach';
    public const CITY_RÜSSELSHEIM   = 'Rüsselsheim';
    public const CITY_WIESBADEN     = 'Wiesbaden';
    public const CITY_MARBURG       = 'Marburg';

    public const MATCHING = [
        self::CITY_DARMSTADT => [
            Identification::KEY_PREFIXES => [
                'd'
            ],
            Identification::KEY_REGEXES => [
                '/darmstadt/mi',
            ]
        ],
        self::CITY_ESCHBORN => [
            Identification::KEY_PREFIXES => [
                'e'
            ],
            Identification::KEY_REGEXES => [
                '/eschborn/mi',
            ]
        ],
        self::CITY_FRANKFURT => [
            Identification::KEY_PREFIXES => [
                'f',
                'fr',
            ],
            Identification::KEY_REGEXES => [
                '/frankfurt am main/mi',
                '/frankfurt/mi'
            ]
        ],
        self::CITY_GIESSEN => [
            Identification::KEY_PREFIXES => [
                'g',
                'gi'
            ],
            Identification::KEY_REGEXES => [
                '/gie(ss|ß)en/mi',
            ]
        ],
        self::CITY_MAINZ => [
            Identification::KEY_PREFIXES => [
                'm',
            ],
            Identification::KEY_REGEXES => [
                '/mainz/mi',
            ]
        ],
        self::CITY_OFFENBACH => [
            Identification::KEY_PREFIXES => [
                'o',
                'of'
            ],
            Identification::KEY_REGEXES => [
                '/offenbach/mi',
            ]
        ],
        self::CITY_RÜSSELSHEIM => [
            Identification::KEY_PREFIXES => [
                'r'
            ],
            Identification::KEY_REGEXES => [
                '/r(u|ue|ü)(s|ss)elsheim/mi',
            ]
        ],
        self::CITY_WIESBADEN => [
            Identification::KEY_PREFIXES => [
                'w',
                'wi'
            ],
            Identification::KEY_REGEXES => [
                '/wiesbaden/mi',
            ]
        ],
        self::CITY_FRANKFURT => [
            Identification::KEY_PREFIXES => [
                'mr',
            ],
            Identification::KEY_REGEXES => [
                '/marburg/mi',
            ]
        ],
    ];

    public static function getAvailableCities(): array
    {
        return [
            self::CITY_DARMSTADT,
            self::CITY_ESCHBORN,
            self::CITY_FRANKFURT,
            self::CITY_GIESSEN,
            self::CITY_MAINZ,
            self::CITY_OFFENBACH,
            self::CITY_RÜSSELSHEIM,
            self::CITY_WIESBADEN,
            self::CITY_MARBURG,
        ];
    }

    /** @var Identification[] */
    private array $identifications;

    public function __construct()
    {
        foreach (self::MATCHING as $city => $match) {
            $this->identifications[$city] = new Identification($match);
        }
    }

    public function identifyCity(?string &$summary, string $longtext): string
    {
        $fallback = 'Andere';

        if (empty($summary)) {
            return $fallback;
        }

        $parts = explode(' ', $summary, 2);
        $prefix = array_shift($parts);

        // We try prefixes first, since it's the much cheaper operation
        foreach ($this->identifications as $cityName => $idenfifier) {
            /** @var Identification $idenfifier */
            if ($idenfifier->doesPrefixMatch($prefix)) {
                $summary = $parts[0];
                return $cityName;
            }
        }

        foreach ($this->identifications as $cityName => $idenfifier) {
            /** @var Identification $idenfifier */
            if ($idenfifier->doesLocationMatch($longtext)) {
                return $cityName;
            }
        }

        return $fallback;
    }
}
