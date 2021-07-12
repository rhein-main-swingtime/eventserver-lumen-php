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
    protected function fetchCategories(): array
    {
        return array_values(
            CalendarEvent::distinct('category')
            ->pluck('category')
            ->toArray()
        );
    }

    /**
     * Returns Categories from DB
     *
     * @return string[]
     */
    protected function fetchCalendars(): array
    {
        return array_values(
            CalendarEvent::distinct('calendar')
            ->pluck('calendar')
            ->toArray()
        );
    }

    /**
     * Returns Cities from DB
     *
     * @return string[]
     */
    protected function fetchCities(): array
    {
        $out = [];
        $cities = array_values(
            EventInstance::distinct('city')
            ->pluck('city')
            ->toArray()
        );
        return $cities;
    }

    /**
     * Returns filters as array
     *
     * @return array
     */
    public function returnFilters(): array
    {
        return [
            'categories'    => $this->fetchCategories(),
            'cities'        => $this->fetchCities(),
            'calendars'     => $this->fetchCalendars(),
        ];
    }

    /**
     * Inserts filter values to dance events collection
     *
     * @param Collection $danceEvents   Dance events
     * @return array
     */
    public function addFilterValues(Collection $danceEvents): array
    {

        $filters = $this->returnFilters();
        $out = [];

        foreach ($filters as $name => $values) {
            $out[$name] = array_merge(
                array_fill_keys(
                    $values,
                    null
                ),
                $danceEvents->countBy(
                    function($event) use ($name) {
                        /* @var $event App\Models\EventInstance */
                        return $event->{self::FILTER_SINGULARS[$name]};
                    }
                )->toArray()
            );
        }

        return $out;

    }

    /**
     * Adds mapping between dates and events
     *
     * @param Collection $danceEvents Dance events collection
     * @return \Illuminate\Support\Collection
     */
    public function addDateMapping(Collection $danceEvents): \Illuminate\Support\Collection
    {
        $datesCollection = $danceEvents->mapToGroups(function ($item, $key) {
            $startDate = (new \DateTimeImmutable($item['start_date_time']))->format('Y-m-d');
            return [$startDate => (int) $item['id']];
        });

        return $datesCollection;
    }

    public function eventsByMonth(Request $request, string $date): \Illuminate\Support\Collection
    {

        [$year, $month] = explode('_', $date);
        $startDate = new \DateTimeImmutable($year . '-' . $month . '-01  00:00:00.000');
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59, 999);

        $query = (new EventInstance())
        ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id')
        ->whereDate('start_date_time', '>=', $startDate->format(EventInstance::DATE_TIME_FORMAT_DB))
        ->whereDate('start_date_time', '<=', $endDate->format(EventInstance::DATE_TIME_FORMAT_DB))
        ->orderBy('start_date_time', 'ASC');

        return $query->get();
    }

    /**
     * Shows Events
     */
    public function showEvents(Request $request, string $category = '')
    {
        $this->validateRequest($request);
        $danceEvents = $this->fetchEvents($request, $category);
        $dates = $this->addDateMapping($danceEvents);
        $filters = $this->addFilterValues($danceEvents);

        return response()->json([
            'filters'       => $filters,
            'dates'         => $dates,
            'danceEvents'   => $danceEvents,
        ]);
    }
}
