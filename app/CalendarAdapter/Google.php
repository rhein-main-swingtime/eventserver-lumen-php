<?php
declare(strict_types=1);

namespace App\CalendarAdapter;

use App\City\CityIdentifier;
use App\DataTransfer\DanceEvent;
use App\DataTransfer\DanceEventCollection;
use DateTimeImmutable;
use Google\Service\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Google implements AdapterParameterInterface
{
    protected Calendar $calendar;
    protected CityIdentifier $cityIdentifier;

    public function __construct(Calendar $calendar, CityIdentifier $cityIdentifier)
    {
        $this->calendar = $calendar;
        $this->cityIdentifier = $cityIdentifier;
    }

    protected function buildArguments(Request $request): array
    {
        $out = [
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => (new DateTimeImmutable($request->get('from') ?? 'today'))->format('c'),
        ];

        if ($to = $request->get(self::TO)) {
            $out['timeMax'] = (new DateTimeImmutable($to))->format('c');
        }

        if ((int) $limit = $request->get(self::LIMIT)) {
            $out['maxResults'] = $limit;
        }

        if ($q = $request->get('q')) {
            $out['q'] = $q;
        }

        return $out;
    }

    private function convertStringToDate(string $input): DateTimeImmutable
    {
        return (new DateTimeImmutable($input));
    }


    protected function buildCollection($results): DanceEventCollection
    {
        $collection = new DanceEventCollection();
        $items = $results->getItems();

        foreach ($items as $item) {
            /** @var Google\Service\Calendar\Event $item */

            try {
                $danceEvent = DanceEvent::createInstance(
                    $item->id,
                    $item->htmlLink,
                    'google',
                    $item->creator->displayName ?? $item->creator->email ?? '',
                    $this->convertStringToDate($item->created),
                    $item->location ?? '',
                    $this->cityIdentifier->identifyCity(
                        $item->summary,
                        implode('\n', [$item->summary, $item->location, $item->description ?? ''])
                    ),
                    $item->summary,
                    $item->description ?? '',
                    $this->convertStringToDate($item->start->dateTime ?? $item->start->date),
                    $this->convertStringToDate($item->end->dateTime ?? $item->end->date)
                );
                $collection->addEvent($danceEvent);
            } catch (\Exception $e) {
                if (config('app.debug') === true) {
                    Log::error(
                        $e->getMessage(),
                        [
                            'file' => $e->getFile().':'.$e->getLine(),
                            'code' => $e->getCode(),
                            'trace' => $e->getTrace()
                        ]
                    );
                } else {
                    throw $e;
                }
            }
        }

        return $collection;
    }

    public function getEventCollection(string $id, Request $request): DanceEventCollection
    {
        $results = $this->calendar->events->listEvents($id, $this->buildArguments($request));
        $collection = $this->buildCollection($results);
        return $collection;
    }
}
