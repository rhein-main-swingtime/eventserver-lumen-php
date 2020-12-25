<?php

namespace App\Http\Controllers;

use App\Events\Event;
use App\Models\CalendarEvent;
use App\Models\EventInstance;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class EventsController extends Controller
{

    private $queries = [];

    private const DEFAULT_LIMIT = 25;

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
        return array_values(
            EventInstance::distinct('city')
            ->pluck('city')
            ->toArray()
        );
    }

    public function returnFilters(Request $request)
    {
        return response()->json(
            [
                'categories'    => $this->fetchCategories(),
                'cities'        => $this->fetchCities(),
                'calendars'     => $this->fetchCalendars(),
            ]
        );
    }

    public function addDateMapping(Collection $danceEvents): \Illuminate\Support\Collection
    {
        $datesCollection = $danceEvents->mapToGroups(function ($item, $key) {
            $startDate = (new \DateTimeImmutable($item['start_date_time']))->format('Y-m-d');
            return [$startDate => (int) $item['id']];
        });

        return $datesCollection;
    }

    /**
     * Shows Events
     */
    public function showEvents(Request $request, string $category = '')
    {
        $this->validateRequest($request);

        // $danceEvents = $this->fetchEvents($request, $category)->mapWithKeys(function($item) {
        //     $id = (integer) $item['id'];
        //     return [$id => $item];
        // });

        $danceEvents = $this->fetchEvents($request, $category);
        $dates = $this->addDateMapping($danceEvents);

        return response()->json([
            'danceEvents'   => $danceEvents,
            'dates'         => $dates,
            // 'filters'       => [
            //     'categories'    => $this->fetchCategories(),
            //     'cities'        => $this->fetchCities(),
            //     'calendars'     => $this->fetchCalendars(),
            // ]
        ]);
    }
}
