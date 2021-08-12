<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\EventInstance;
use DateTime;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class EventsController extends Controller
{

    private const DEFAULT_LIMIT = 25;

    private const FILTER_SINGULARS = [
        'categories' => 'category',
        'cities' => 'city',
        'calendars' => 'calendar'
    ];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    protected function validateRequest(Request $request): void
    {
        $this->validate($request, [
            'cities'        => 'array',
            'skip'          => 'integer|min:1',
            'limit'         => 'integer|min:1',
            'calendars'     => 'array',
            'categories'    => 'array',
        ]);
    }

    protected function validateEventRequest(Request $request): void {
        $this->validate(
            $request,
            [
                'from'  => 'date|required',
                'to'    => 'date|required|after:from',
            ]
        );
    }

    /**
     * fetchEvents
     *
     * @param  Request $request
     * @return Illuminate\Database\Eloquent\Collection
     */
    protected function fetchEvents(Request $request): \Illuminate\Database\Eloquent\Collection
    {

        $limit = (
            $request->input('limit') === null
            ? self::DEFAULT_LIMIT
            : (int) $request->input('limit')
        );

        $skip = (int) $request->input('skip');

        $categories = $request->input('categories');
        $cities = $request->input('cities');
        $calendars = $request->input('calendars');

        $startDate = $request->input('startDate');
        if ($startDate === null) {
            $startDate = (new DateTime('today'))->setTime(0, 0, 0);
        } else {
            $startDate = (new DateTime($startDate));
        }

        $endDate = $request->input('endDate');
        if ($endDate === null) {
            $endDate = (new DateTime('last day of this month'))->setTime(23, 59, 59);
        } else {
            $endDate = (new DateTime($endDate));
        }

        $query = (new EventInstance())
            ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id')
            ->when($categories, function ($query, $categories) {
                $query->wherein('category', $categories);
            })
            ->when($cities, function ($query, $cities) {
                $query->wherein('city', $cities);
            })
            ->when($calendars, function ($query, $calendars) {
                $query->wherein('calendar', $calendars);
            })
            ->whereDate('start_date_time', '>=', $startDate->format(EventInstance::DATE_TIME_FORMAT_DB))
            ->orderBy('start_date_time', 'ASC');

        if ($skip > 0) {
            $query->skip($skip);
        }

        $query->limit($limit);

        return $query->get();
    }

    /**
     * Returns Categories from DB
     *
     * @return string[]
     */
    protected function fetchCategories(array $selected): array
    {

        $out = [];

        $categories = array_values(
            CalendarEvent::distinct('category')
            ->pluck('category')
            ->toArray()
        );

        foreach ($categories as $category) {
            $count = (new EventInstance())
            ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id')
            ->whereCategory($category)
            ->count();

            $out[$category] = [
                'count' => $count,
                'selected' => in_array($category, $selected)
            ];
        }

        return $out;
    }

    /**
     * Returns Categories from DB
     *
     * @return string[]
     */
    protected function fetchCalendars(array $selected): array
    {

        $out = [];

        $calendars = array_values(
            CalendarEvent::distinct('calendar')
            ->pluck('calendar')
            ->toArray()
        );

        foreach ($calendars as $calendar) {
            $count = (new EventInstance())
            ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id')
            ->whereCalendar($calendar)
            ->count();

            $out[$calendar] = [
                'count' => $count,
                'selected' => in_array($calendar, $selected)
            ];
        }

        return $out;

    }

    /**
     * Returns Cities from DB
     *
     * @return string[]
     */
    protected function fetchCities(array $selected): array
    {
        $out = [];
        $cities = array_values(
            EventInstance::distinct('city')
            ->pluck('city')
            ->toArray()
        );

        foreach($cities as $city) {
            $count = EventInstance::query()->select()->whereCity($city)->count();
            $out[$city] = [
                'count' => $count,
                'selected' => in_array($city, $selected)
            ];
        }

        return $out;
    }

    /**
     * Returns filters as array
     *
     * @return array
     */
    public function returnFilters(Request $request): array
    {
        $instance = (new EventInstance())
            ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id');

        foreach (self::FILTER_SINGULARS as $plural => $singular) {
            $get = $request->input($plural);
            if ($get === null || count($get) === 0) {
                continue;
            }

            $instance->whereIn($singular, $get);
        }

        $total = [];

        foreach (array_values(self::FILTER_SINGULARS) as $item) {

            if ($item === 'city') {
                $out[$item] = EventInstance::distinct($item)
                ->pluck($item)
                ->toArray();
                continue;
            }

            $out[$item] = CalendarEvent::distinct($item)
                ->pluck($item)
                ->toArray();
        }

        // return [
        //     'category'    => $this->fetchCategories($selectedCategories ?? []),
        //     'city'        => $this->fetchCities($selectedCities ?? []),
        //     'calendar'     => $this->fetchCalendars($selectedCalendars ?? []),
        // ];
    }

    // /**
    //  * Inserts filter values to dance events collection
    //  *
    //  * @param Collection $danceEvents   Dance events
    //  * @return array
    //  */
    // public function addFilterValues(Collection $danceEvents): array
    // {

    //     $filters = $this->returnFilters();
    //     $out = [];

    //     foreach ($filters as $name => $values) {
    //         $out[$name] = array_merge(
    //             array_fill_keys(
    //                 $values,
    //                 null
    //             ),
    //             $danceEvents->countBy(
    //                 function($event) use ($name) {
    //                     /* @var $event App\Models\EventInstance */
    //                     return $event->{self::FILTER_SINGULARS[$name]};
    //                 }
    //             )->toArray()
    //         );
    //     }

    //     return $out;

    // }

    /**
     * Adds mapping between dates and events
     *
     * @param Collection $danceEvents Dance events collection
     * @return \Illuminate\Support\Collection
     */
    public function addDateMapping(Collection $danceEvents): \Illuminate\Support\Collection
    {
        $datesCollection = $danceEvents->mapToGroups(function ($item, $key) {
            $startDate = (new \DateTimeImmutable($item['start_date_time']))->format('Y-n-d');
            return [$startDate => (int) $item['id']];
        });

        return $datesCollection;
    }

    /**
     * Returns events by month
     *
     * @param Request $request
     * @param string $date
     * @return \Illuminate\Support\Collection[]
     */
    public function eventsByMonth(Request $request, string $date): array
    {

        [$year, $month] = explode('-', $date);
        $startDate = new \DateTimeImmutable($year . '-' . $month . '-01  00:00:00.000');
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59, 999);

        $query = (new EventInstance())
        ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id')
        ->whereDate('start_date_time', '>=', $startDate->format(EventInstance::DATE_TIME_FORMAT_DB))
        ->whereDate('end_date_time', '>=', (new DateTimeImmutable())->format(EventInstance::DATE_TIME_FORMAT_DB))
        ->whereDate('start_date_time', '<=', $endDate->format(EventInstance::DATE_TIME_FORMAT_DB))
        ->orderBy('start_date_time', 'ASC');

        $events = $query->get();
        $dates = $this->addDateMapping($events);

        return [
            'events' => $events,
            'dates' => $dates,
        ];
    }

    public function findEvents(Request $request): \Illuminate\Support\Collection {

        $this->validateEventRequest($request);

        $fromDate = new DateTimeImmutable($request->input('from'));
        $endDate = new DateTimeImmutable($request->input('to'));

        return (new EventInstance())
            ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id')
            ->whereDate('start_date_time', '>=', $fromDate->format(EventInstance::DATE_TIME_FORMAT_DB))
            // ->whereDate('end_date_time', '>=', (new DateTimeImmutable())->format(EventInstance::DATE_TIME_FORMAT_DB))
            ->whereDate('start_date_time', '<=', $endDate->format(EventInstance::DATE_TIME_FORMAT_DB))
            ->orderBy('start_date_time', 'ASC')->get();

    }

    /**
     * Shows Events
     */
    public function showEvents(Request $request, string $category = '')
    {
        $this->validateRequest($request);
        $danceEvents = $this->fetchEvents($request, $category);
        $dates = $this->addDateMapping($danceEvents);
        // $filters = $this->addFilterValues($danceEvents);

        return response()->json([
            //'filters'       => $filters,
            'danceEvents'   => $danceEvents,
            'dates'         => $dates,
            'danceEvents'   => $danceEvents,
        ]);
    }
}
