<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class EventsController extends Controller
{

    private $queries = [];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    protected function validateRequest(Request $request): void {
        $this->validate($request, [
            'skip'          => 'integer|min:0',
            'cities'        => 'array',
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

        $limit = (int) $request->input('limit');
        $skip = (int) $request->input('skip');

        $categories = $request->input('categories');
        $cities = $request->input('cities');
        $calendars = $request->input('calendars');

        $query = (new CalendarEvent())
            ->when($categories, function ($query, $categories) {
                $query->wherein('category', $categories);
            })
            ->when($cities, function ($query, $cities) {
                $query->wherein('city', $cities);
            })
            ->when($calendars, function ($query, $calendars) {
                $query->wherein('calendar', $calendars);
            })
            ->when($limit, function ($query, $limit) {
                $query->limit($limit);
            })
            ->when($skip, function ($query, $skip) {
                $query->skip($skip);
            })
            ->whereDate('end_date_time', '>=', date('Y-m-d i:m:s'))
            ->orderBy('start_date_time', 'ASC');

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
            CalendarEvent::distinct('city')
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

    /**
     * Shows Events
     */
    public function showEvents(Request $request, string $category = '')
    {
        $this->validateRequest($request);

        return response()->json([
            'danceEvents'   => $this->fetchEvents($request, $category),
        ]);
    }
}
