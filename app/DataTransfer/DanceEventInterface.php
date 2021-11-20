<?php

namespace App\DataTransfer;

use DateTimeInterface;

/**
 * Interface describes a dance event
 *
 * Atm we're only using google, however this is a first step to abstraction.
 */
interface DanceEventInterface
{

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
    ): DanceEventInterface;

    public function getId(): string;
    public function getForeignUrl(): string;
    public function getSource(): string;
    public function getCreator(): string;
    public function getCreated(): ?string;
    public function getLocation(): string;
    public function getCity(): string;
    public function getSummary(): string;
    public function getDescription(): string;
    public function getStartDateTime(): string;
    public function getEndDateTime(): string;
    public function toArray(): array;
}
