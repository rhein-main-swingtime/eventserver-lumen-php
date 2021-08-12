<?php
/**
 * @package     Product
 * @author      mAm <mamreezaa@gmail.com>
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google_Service_Calendar;
use App\Models\EventInstance;
use App\Models\CalendarEvent;
use DateTimeImmutable;
use Google_Service_Calendar_EventDateTime;
use Log;

/**
 * Class RouteList
 * @package App\Console\Commands
 */
class ImportCalendarEvents extends Command
{

    private const STATUS_ERROR = 'error';
    private const STATUS_SUCCESS = 'success';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:import';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports events from Calendar Sources.';

    /**
     * Calendar Service
     *
     * @var Google_Service_Calendar $googleCalendar
     */
    protected $googleCalendar;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Google_Service_Calendar $googleCalendar)
    {
        parent::__construct();
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
            . env('SOURCES_GOOGLE');

        if (config('local', false)) {
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
            'Darmstadt' => "darmsta(dt|tt|dd)",
            'Frankfurt' => "frankfurt",
            'Gießen' => "(G|g)ie(ss|ß)en",
            'Mainz' => "mainz",
            'Offenbach' => "offenbach",
            'Rüsselsheim' => "r(ü|ue)(ss|ß)elsheim",
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

    private function getOffset(\Google_Service_Calendar_EventDateTime $date): float  {
        $val = substr($date->dateTime, -6);
        return floatval($val);
    }

    /**
     * Imports all sources
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function importAll(): array
    {

        $updated = [
            'instances' => 0,
            'events' => 0,
        ];
        $deleted = [
            'instances' => 0,
            'events' => 0,
        ];
        $status = self::STATUS_SUCCESS;
        $errors = [];
        $updatedEventIDs = [];

        $paramters = [
            'maxResults' => 2500,
            'singleEvents' => false,
            'timeMin' => (new \DateTimeImmutable('now'))->format('Y-m-d\TH:i:sP'),
            'timeMax'=> (new \DateTimeImmutable('+4 year'))->format('Y-m-d\TH:i:sP'),
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
                    print_r($e->getMessage());
                }

                foreach ($instances->getItems() as $instance) {
                    try {
                        $instance_id = $instance->getId();
                        $updatedInstanceIDs[] = $instance_id;

                        $city = $this->retrieveCity(
                            $instance->getLocation()
                            . ' ' . $instance->getSummary()
                            . ' ' . $instance->getDescription()
                        );

                        $summary = $this->retrieveSummary($city, $instance->getSummary());

                        EventInstance::updateOrCreate(
                            [
                                'instance_id' => $instance_id,
                                'event_id'    => $eventId,
                            ],
                            [
                                'instance_id'               => $instance_id,
                                'event_id'                  => $eventId,
                                'summary'                   => $summary,
                                'description'               => $this->formatDescription($instance->getDescription() ?? ''),
                                'location'                  => $instance->getLocation(),
                                'city'                      => $city,
                                'start_date_time'           => $instance->getStart(),
                                'end_date_time'             => $instance->getEnd(),
                                'start_date_time_offset'    => $this->getOffset($instance->getStart()),
                                'end_date_time_offset'      => $this->getOffset($instance->getStart()),
                            ]
                        );
                        $updated['instances']++;
                    } catch (\Exception $e) {
                        print_r($e->getMessage());
                        die;
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

        return [
            'status'    => $status,
            'updated'   => $updated,
            'deleted'   => $deleted,
            'errors'    => count($errors) > 0 ? $errors : 'none',
        ];
    }

    private function formatDescription(string $desc): string
    {
        if ($desc !== strip_tags($desc)) { // already html
            return $desc;
        }

        return nl2br($desc);
    }

    private function retrieveSummary(string $city, $summary) {
        $map = [
            'frankfurt' => [
                'f'
            ],
            'darmstadt' => [
                'da'
            ],
            'mainz' => [
                'mz', 'm'
            ]
        ];

        $prefixesForCity = $map[strtolower($city)] ?? null;
        if ($city === 'Andere' || $prefixesForCity === null) {
            return $summary;
        }

        foreach($prefixesForCity as $prefix) {
            if (str_starts_with(strtolower($summary), $prefix . ' ')) {

                $length = mb_strlen($summary);
                $prefixLength = mb_strlen($prefix) + 1;

                return mb_substr(
                    $summary,
                    ($length - $prefixLength) * -1
                );
            }
        }


        return $summary;
    }

    /**
     * Execute the console command.
     *
     * @param  \App\Support\DripEmailer  $drip
     * @return mixed
     */
    public function handle()
    {
        Log::info('Event Import Started');

        $result = $this->importAll();
        $status = $result['status'];
        $result = json_encode($result, JSON_PRETTY_PRINT);

        if ($status === self::STATUS_ERROR) {
            Log::error($result);
        } else {
            Log::info($result);
        }
    }

}
