<?php
/**
 * @package     Product
 * @author      mAm <mamreezaa@gmail.com>
 */

namespace App\Console\Commands;

use Carbon\Carbon;
use App\City\CityIdentifier;
use Illuminate\Console\Command;
use App\Models\EventInstance;
use App\Models\CalendarEvent;
use DateTime;
use DateTimeImmutable;
use Log;
use Google\Service\Calendar;
use Google\Service\Classroom\Form;
use HtmlSanitizer\SanitizerInterface;

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

    protected Calendar $googleCalendar;
    protected CityIdentifier $cityIdentifier;
    protected SanitizerInterface $danceEventSanitizer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        Calendar $googleCalendar,
        CityIdentifier $cityIdentifier,
        SanitizerInterface $danceEventSanitizer
    ) {
        parent::__construct();
        $this->googleCalendar = $googleCalendar;
        $this->cityIdentifier = $cityIdentifier;
        $this->danceEventSanitizer = $danceEventSanitizer;
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

    private function removeOldInstances(): int {
        $cutoffTime = (new DateTime('-1 month'))->format('Y-m-d h:i:s');
        return EventInstance::where('end_date_time', '<', $cutoffTime)->delete();
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
            ->whereNotIn('id', $ids)
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

    private function getOffset(?\Google\Service\Calendar\EventDateTime $date): float
    {
        if ($date === null) {
            return 0;
        }

        $val = substr($date->dateTime, -6);
        return floatval($val);
    }

    private function getSanitizedDesc(string $desc): string
    {
        if ($desc === strip_tags($desc)) { // We're dealing with plaintext
            return nl2br($desc);
        }

        return $this->danceEventSanitizer->sanitize($desc);
    }

    private function unfuckDate(\Google\Service\Calendar\EventDateTime $value): string
    {
        $val = $value->getDateTime() ?? $value->getDate();
        $tz = $value->getTimeZone();

        return (Carbon::parse($val, $tz))->format(EventInstance::DATE_TIME_FORMAT_DB);
    }

    private function updateOrCreateEventInstance(string $eventId, $instance): ?string
    {
        /* @var Google\Service\Calendar\Event $instance */
        $instance_id = $instance->getId();

        $summary = $instance->getSummary();

        $city = $this->cityIdentifier->identifyCity(
            $summary,
            implode('\n', [
                $instance->summary ?? '',
                $instance->location ?? '',
                $instance->description ?? ''
            ])
        );
        try {


            $dbInstance = EventInstance::updateOrCreate(
                [
                    'summary'                   => $summary,
                    'start_date_time'           => $this->unfuckDate($instance->getStart()),
                    'end_date_time'             => $this->unfuckDate($instance->getEnd()),
                    'instance_id'               => $instance_id,
                ],
                [
                    'instance_id'               => $instance_id,
                    'event_id'                  => $eventId,
                    'summary'                   => $summary,
                    'description'               => $this->getSanitizedDesc(
                        $instance->getDescription() ?? ''
                    ),
                    'location'                  => $instance->getLocation(),
                    'city'                      => $city,
                    'foreign_url'               => $instance->htmlLink,
                    'start_date_time'           => $this->unfuckDate($instance->getStart()),
                    'end_date_time'             => $this->unfuckDate($instance->getEnd()),
                    'start_date_time_offset'    => $this->getOffset($instance->getStart()),
                    'end_date_time_offset'      => $this->getOffset($instance->getStart()),
                    'serialized'                => json_encode($instance),
                ]
            );
            return $dbInstance->id;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }

    /**
     * Imports all sources
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function importAll(): array
    {

        $this->removeOldInstances(); // a little housekeeping

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

        $timeMin = (new DateTimeImmutable('today'))->format('c');
        $timeMax = (new DateTimeImmutable('+3 year'))->format('c');

        $parameters = [
            'maxResults' => 2500,
            'singleEvents' => false,
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'showDeleted' => true,
            // 'showHiddenInvitations' => true,
        ];

        foreach ($this->getSources() as $name => $data) {
            $eventList = $this->googleCalendar->events->listEvents($data['id'], $parameters);

            foreach ($eventList as $event) {
                if ($event->status === 'cancelled') {
                    continue;
                }

                $eventId = $event->getId();
                $recurrence = $event->getRecurrence();

                if (gettype($recurrence) === 'array') {
                    $recurrence = implode(' | ', $recurrence);
                }

                $updatedInstanceIDs = [];

                $instances = $this->googleCalendar->events->instances(
                    $data['id'],
                    $event->getId(),
                    [
                        'timeMin' => $timeMin,
                        'timeMax' => $timeMax,
                    ]
                );

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
                            'recurrence'    => $recurrence,
                            'serialized'    => json_encode($event),
                        ]
                    );
                    $updatedEventIDs[] = $eventId;
                    $updated['events']++;
                } catch (\Exception $e) {
                    $errors[] = $eventId;
                    Log::error(
                        "Error creating event",
                        [
                            $eventId,
                            $e->getMessage()
                        ]
                    );
                }

                /** @var []Google\Service\Calendar\Event */
                $instanceItems =  $instances->getItems();

                if (count($instanceItems) === 0) {
                    $updatedInstanceIDs[] = $this->updateOrCreateEventInstance($eventId, $event);
                } else {
                    foreach ($instanceItems as $instance) {
                        try {
                            $updatedInstanceIDs[] = $this->updateOrCreateEventInstance($eventId, $instance);
                            $updated['instances']++;
                        } catch (\Exception $e) {
                            $errors[] = $eventId;
                            Log::error(
                                "Error creating instance",
                                [
                                    $eventId,
                                    $e->getMessage()
                                ]
                            );
                            continue;
                        }
                    }
                }

                $updatedInstanceIDs = array_filter($updatedInstanceIDs);

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

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
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
