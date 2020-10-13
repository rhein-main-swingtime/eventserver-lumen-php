<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;

class EventsController extends Controller
{

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
            'skip'      => 'integer|min:0',
            'city'      => 'alpha_dash',
            'limit'     => 'integer|min:1',
            'calendar'  => 'alpha_dash',
        ]);
    }

    protected function fetchEvents(Request $request, string $category = ''): \Illuminate\Database\Eloquent\Collection
    {

        $limit = (int) $request->input('limit');
        $skip = (int) $request->input('skip');

        $eventQuery = (new CalendarEvent())->newQuery();

        $where = [];

        if ($category !== '') {
            $where['category'] = $category;
        }

        if ($request->input('city') !== null) {
            $where['city'] = $request->input('city');
        }

        $eventQuery->where($where);

        if ($limit > 0) {
            $eventQuery->limit($limit);
        }

        if ($skip > 0) {
            $eventQuery->skip($skip);
        }

        return $eventQuery->get();

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

    /**
     * Shows Events
     */
    public function showEvents(Request $request, string $category = '')
    {
        $this->validateRequest($request);

        return response()->json([
            'danceEvents'   => $this->fetchEvents($request, $category),
            'categories'    => $this->fetchCategories(),
            'calendars'     => $this->fetchCalendars(),
            'cities'        => $this->fetchCities(),
        ]
        );
    }
}
