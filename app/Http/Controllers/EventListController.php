<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\CalendarAdapter\Google;
use App\City\CityIdentifier;
use App\DataTransfer\DanceEvent;
use App\DataTransfer\DanceEventCollection;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\DateImmutableType;
use Google\Service\Calendar;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class EventListController extends Controller
{
    protected Calendar $calendar;

    /**
     * Returns Events Sources to update
     *
     * @return array[]
     */
    protected function getSources(): array
    {

        $sources = require __DIR__
            . '/../../../'
            . env('SOURCES_GOOGLE');

        if (config('local', false)) {
            $sources['Testcalendar'] = [
                'id' => 'flehdvi6amllkm5cm2dd62qnoc@group.calendar.google.com',
                'category' => 'Test'
            ];
        }

        return $sources;
    }

    protected function validateRequest(Request $request): void
    {
        $this->validate($request, [
            'from' => 'date|required_with:to',
            'to' => 'date|after:from',
            'limit' => 'integer',
            'page' => 'integer'
        ]);
    }

    protected function validateCalendar(string $calendar, array $sources)
    {
        $validator = Validator::make(['calendar' => urldecode($calendar)], [
            'calendar' => [
                'required',
                Rule::in(array_keys($sources))
            ]
        ]);
        $validator->validate();
    }

    public function fetchEvents(Request $request, string $calendar, string $version)
    {
        $calendar = urldecode($calendar);
        $this->validateCalendar($calendar, $this->getSources());
        $this->validateRequest($request);

        /** @var \App\CalendarAdapter\Google */
        $adapter = app(Google::class);
        $collection = $adapter->getEventCollection(
            $this->getSources()[$calendar]['id'],
            $request
        );

        return new JsonResponse(
            [
                'eventCollection' => $collection->toArray(),
                'cities' => CityIdentifier::getAvailableCities()
            ]
        );
    }
}
