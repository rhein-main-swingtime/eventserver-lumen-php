<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EventInstance;
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
     * @param string    $eventId    Event ID
     * @param string[]  $ids        Instance IDs
     * @return int
     */
    private function removeOutdatedInstances(string $eventId, array $ids): int
    {
        return EventInstance::where('event_id', $eventId)
            ->whereNotIn('instance_id', $ids)
            ->delete();
    }

    private function removeOutdatedEvents(array $eventIDs): int
    {
        return CalendarEvent::whereNotIn('event_id', $eventIDs)
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

    private function getCreator($event): string
    {

        $name = '';
        $creator = $event->getCreator();

        if ($creator === null) {
            return $name;
        }

        try {
            $name .= $creator->getDisplayName();
            $name .= ' <' . $creator->getEmail() . '>';
        } catch (\Exception $e) {
            return $name;
        }

        return $name;
    }

    /**
     * Imports all sources
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function importAll(): \Illuminate\Http\JsonResponse
    {

        $updated = [
            'instances' => 0,
            'events' => 0,
        ];
        $deleted = [
            'instances' => 0,
            'events' => 0,
        ];
        $status = 200;
        $errors = [];
        $updatedEventIDs = [];

        $paramters = [
            'maxResults' => 2500,
            'singleEvents' => false,
            'timeMin' => (new \DateTimeImmutable('now'))->format('Y-m-d\TH:i:sP'),
            'timeMax'=> (new \DateTimeImmutable('+2 year'))->format('Y-m-d\TH:i:sP'),
        ];


        foreach ($this->getSources() as $name => $data) {
            $eventList = $this->googleCalendar->events->listEvents($data['id'], $paramters);

            foreach ($eventList as $event) {
                $eventId = $event->getId();
                $recurrence = $event->getRecurrence();
                if (gettype($recurrence) === 'array') {
                    $recurrence = implode(' | ', $recurrence);
                }

                $updatedInstanceIDs = [];
                $instances = $this->googleCalendar->events->instances($data['id'], $event->getId());

                try {
                    CalendarEvent::updateOrCreate(
                        [
                            'event_id' => $eventId,
                        ],
                        [
                            'event_id'      => $eventId,
                            'creator'       => $this->getCreator($event),
                            'calendar'      => $name,
                            'category'      => $data['category'],
                            'updated'       => $event->getUpdated(),
                            'created'       => $event->getCreated(),
                            'recurrence'    => $recurrence
                        ]
                    );
                    $updatedEventIDs[] = $eventId;
                    $updated['events']++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode()
                    ];
                    $status = 500;
                    continue;
                }

                foreach ($instances->getItems() as $instance) {
                    try {
                        $instance_id = $instance->getId();
                        $updatedInstanceIDs[] = $instance_id;
                        $instance->getRecurrence();
                        EventInstance::updateOrCreate(
                            [
                                'instance_id' => $instance_id,
                                'event_id'    => $eventId,
                            ],
                            [
                                'instance_id'       => $instance_id,
                                'event_id'          => $eventId,
                                'summary'           => $instance->getSummary(),
                                'description'       => $instance->getDescription() ?? '',
                                'location'          => $instance->getLocation(),
                                'start_date_time'   => $instance->getStart(),
                                'end_date_time'     => $instance->getEnd(),
                                'city'              => $this->retrieveCity(
                                    $instance->getLocation()
                                    . ' ' . $instance->getSummary()
                                    . ' ' . $instance->getDescription()
                                ),
                            ]
                        );
                        $updated['instances']++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode()
                        ];
                        $status = 500;
                    }
                }

                if (count($updatedInstanceIDs)) {
                    $deleted['instances'] += $this->removeOutdatedInstances($eventId, $updatedInstanceIDs);
                }
            }

        }

        if (count($updatedEventIDs)) {
            $deleted['events'] += $this->removeOutdatedEvents($updatedEventIDs);
        }

        return response()->json([
            'updated' => $updated,
            'deleted' => $deleted,
            'errors' => count($errors) > 0 ? $errors : 'none',
        ], $status);
    }
}
