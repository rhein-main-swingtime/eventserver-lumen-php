<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use App\Providers\GoogleCalendarServiceProvider;
use Google_Service;
use Google_Service_Calendar;
use RegexIterator;

/**
 * ImportController
 *
 * @todo Import method returns wrong counts, this sucks
 * @todo PHPStan finds errors, somehow related to laravel magic?
 * @todo Extra tables for location and creators would be nice
 *
 */
class ImportController extends Controller
{

    /**
     * Calendar Service
     *
     * @var Google_Service_Calendar $googleCalendar
     */
    protected $googleCalendar;

    public function __construct(Google_Service_Calendar $googleCalendar)
    {
        $this->googleCalendar = $googleCalendar;
    }

    /**
     * Returns Events Sources to update
     *
     * @return array[]
     */
    protected function getSources(): array
    {
        $sources = require __DIR__
                    . '/../../../'
                    . env('SOURCES_GOOGLE', '');

        if (env('local', false)) {
            $sources['Testcalendar'] = [
                'id' => 'flehdvi6amllkm5cm2dd62qnoc@group.calendar.google.com',
                'category' => 'Test'
            ];
        }

        return $sources;
    }

    /**
     * Removes outdated entries
     *
     * @param string        $calendar  Calendar
     * @param string[]       $ids        IDs
     * @return int
     */
    private function removeOutdated(string $calendar, array $ids): int
    {
        return CalendarEvent::where('calendar', $calendar)
            ->whereNotIn('event_id', $ids)
            ->delete();
    }

    protected function retrieveCity(string $location): string
    {

        $cities = [
            'Offenbach' => "offenbach",
            'Gießen' => "(G|g)ie(ss|ß)en",
            'Frankfurt' => "frankfurt",
            'Darmstadt' => "darmsta(dt|tt|dd)",
        ];

        foreach ($cities as $city => $regex) {
            $found = preg_match('/' . $regex.'/mi', $location);

            if ($found > 0) {
                return $city;
            }
        }

        return 'Andere';

    }

    /**
     * Imports all sources
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function importAll(): \Illuminate\Http\JsonResponse
    {

        $updated = 0;
        $deleted = 0;
        $status = 200;
        $errors = [];

        $paramters = [
            'maxResults' => 2500,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => (new \DateTimeImmutable('now'))->format('Y-m-d\TH:i:sP'),
            'timeMax'=> (new \DateTimeImmutable('+2 year'))->format('Y-m-d\TH:i:sP'),
        ];

        foreach ($this->getSources() as $name => $data) {
            $events = $this->googleCalendar->events->listEvents($data['id'], $paramters);

            $updatedIDs = [];

            /** @var \Google_Service_Calendar_Event $nextEvent */
            while ($nextEvent = $events->next()) {
                try {
                    $event_id = $nextEvent->getId();
                    $updatedIDs[] = $event_id;

                    CalendarEvent::updateOrCreate(
                        [
                            'event_id' => $event_id,
                            'updated' => $nextEvent->getUpdated()
                        ],
                        [
                            'summary' => $nextEvent->getSummary(),
                            'description' => $nextEvent->getDescription() ?? '',
                            'event_id' => $event_id,
                            'created' => $nextEvent->getCreated(),
                            'creator' => $nextEvent->getCreator()->getEmail(),
                            'location' => $nextEvent->getLocation(),
                            'category' => $data['category'],
                            'start_date_time' => $nextEvent->getStart(),
                            'end_date_time' => $nextEvent->getEnd(),
                            'calendar' => $name,
                            'city' => $this->retrieveCity(
                                $nextEvent->getLocation()
                                . ' ' . $nextEvent->getSummary()
                                . ' ' . $nextEvent->getDescription()
                            ),
                            'reccurence' => $nextEvent->getRecurringEventId() ?? ''
                        ]
                    );

                    $updated++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode()
                    ];
                    $status = 500;
                }
            }
            $deleted += $this->removeOutdated($name, $updatedIDs);
        }

        return response()->json([
            'updated' => $updated,
            'deleted' => $deleted,
            'errors' => count($errors) > 0 ? $errors : 'none',
        ], $status);
    }
}
