<?php
declare(strict_types=1);

namespace App\DataTransfer;

class DanceEventCollection
{

    /** @var DanceEventInterface[] */
    protected $events;

    /** @var string|null */
    protected $nextPageToken = null;

    public function addEvent(DanceEventInterface $event): void
    {
        $this->events[] = $event;
    }

    public function toArray(): array
    {
        $out = [
            'nextPageToken' => $this->nextPageToken,
            'events' => []
        ];

        foreach ($this->events ?? [] as $event) {
            $out['events'][] = $event->toArray();
        }

        return $out;
    }

    public function setNextPageToken(string $token)
    {
        $this->nextPageToken = $token;
    }
}
