<?php
declare(strict_types=1);

namespace App\City;

class Identification
{
    /** @var string[] */
    private array $locationRegexes;

    private array $prefixes;

    public const KEY_PREFIXES = 'prefixes';
    public const KEY_REGEXES = 'regexes';

    public function __construct($params)
    {
        $this->prefixes = $params[self::KEY_PREFIXES];
        $this->locationRegexes = $params[self::KEY_REGEXES];
    }

    public function doesPrefixMatch(string $prefix): bool
    {
        return in_array($prefix, $this->prefixes);
    }

    public function doesLocationMatch(string $location): bool
    {
        foreach ($this->locationRegexes as $regex) {
            $result = (bool) preg_match($regex, $location);
            if ($result === true) {
                return $result;
            }
        }
        return false;
    }
}
