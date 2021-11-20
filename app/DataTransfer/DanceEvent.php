<?php
declare(strict_types=1);

namespace App\DataTransfer;

use App\HtmlSanitizer\DanceEventSanitizer;
use App\HtmlSanitizer\DanceEventSanitizerInterface;
use DateTimeInterface;
use HtmlSanitizer\Sanitizer;

class DanceEvent implements DanceEventInterface
{

    protected string $id;
    protected string $foreignUrl;
    protected string $source;
    protected string $creator;
    protected ?DateTimeInterface $created;
    protected string $location;
    protected string $city;
    protected string $summary;
    protected string $description;
    protected DateTimeInterface $startDateTime;
    protected DateTimeInterface $endDateTime;
    protected Sanitizer $sanitizer;

    private function __construct(
        string $id,
        string $foreignUrl,
        string $source,
        string $creator,
        ?DateTimeInterface $created,
        string $location,
        string $city,
        string $summary,
        string $description,
        DateTimeInterface $startDateTime,
        DateTimeInterface $endDateTime,
        Sanitizer $sanitizer
    ) {
        $this->id = $id;
        $this->foreignUrl = $foreignUrl;
        $this->source = $source;
        $this->creator = $creator;
        $this->created = $created;
        $this->location = $location;
        $this->city = $city;
        $this->summary = $summary;
        $this->description = $description;
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
        $this->sanitizer = $sanitizer;
    }

    public static function createInstance(
        string $id,
        string $foreignUrl,
        string $source,
        string $creator,
        ?DateTimeInterface $created,
        string $location,
        string $city,
        string $summary,
        string $description,
        DateTimeInterface $startDateTime,
        DateTimeInterface $endDateTime
    ): DanceEventInterface {
        $sanitizer = app(\App\HtmlSanitizer\DanceEventSanitizerInterface::class);
        return new self(
            $id,
            $foreignUrl,
            $source,
            $creator,
            $created,
            $location,
            $city,
            $summary,
            $description,
            $startDateTime,
            $endDateTime,
            $sanitizer
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getForeignUrl(): string
    {
        return $this->foreignUrl;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function getCreated(): ?string
    {
        if ($this->created !== null) {
            return $this->created->format('c');
        }

        return $this->created;
    }

    public function getLocation(): string
    {
        return str_replace(', ', ',<br>', $this->location);
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getSummary(): string
    {
        return trim($this->summary);
    }

    public function getDescription(): string
    {
        return $this->normalizeSummary($this->description);
    }

    public function getStartDateTime(): string
    {
        return $this->startDateTime->format('c');
    }

    public function getEndDateTime(): string
    {
        return $this->endDateTime->format('c');
    }

    public function toArray(): array
    {
        $props = array_keys(get_object_vars($this));

        $out = [];
        foreach ($props as $prop) {
            $method = 'get' . ucfirst($prop);
            if (method_exists($this, $method)) {
                $out[$prop] = $this->{$method}();
            }
        }
        return $out;
    }

    private function isHtml(string $content): bool
    {
        return strip_tags($content) !== $content;
    }

    private function normalizeSummary(string $summary): string
    {
        if ($this->isHtml($summary)) {
            return $this->sanitizer->sanitize($summary);
        }

        return nl2br(htmlentities($summary));
    }
}
