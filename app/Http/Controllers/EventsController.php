<?php

namespace App\Http\Controllers;

use App\Http\Parameter\EventParameterInterface;
use App\Models\CalendarEvent;
use App\Models\EventInstance;
use DateTime;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class EventsController extends Controller implements EventParameterInterface
{

    private const DEFAULT_LIMIT = 25;

    protected function validateRequest(Request $request): void
    {
        $this->validate($request, self::VALIDATIONS);
    }

    /**
     * fetchEvents
     *
     * @param  Request $request
     * @return Illuminate\Database\Eloquent\Collection
     */
    protected function fetchEvents(Request $request): \Illuminate\Database\Eloquent\Collection
    {

        $limit = $request->input(self::PARAMETER_LIMIT) ?? self::DEFAULT_LIMIT;

        $skip = (int) $request->input('skip');

        $categories = $request->input(self::PARAMETER_CATEGORY);
        $cities = $request->input(self::PARAMETER_CITY);
        $calendars = $request->input(self::PARAMETER_CALENDAR);

        $startDate = $request->input(self::PARAMETER_FROM);
        if ($startDate === null) {
            $startDate = (new DateTime('today'))->setTime(0, 0, 0);
        } else {
            $startDate = (new DateTime($startDate));
        }

        $endDate = $request->input(self::PARAMETER_TO);
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

    /**
     * Shows Events
     */
    public function listEvents(Request $request)
    {
        $this->validateRequest($request);
        $danceEvents = $this->fetchEvents($request);
        $dates = $this->addDateMapping($danceEvents);

        return response()->json([
            'dates'         => $dates,
            'danceEvents'   => $danceEvents,
        ]);
    }
}
