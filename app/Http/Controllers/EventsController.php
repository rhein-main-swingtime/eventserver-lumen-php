<?php

namespace App\Http\Controllers;

use App\Http\Parameter\EventParameterInterface;
use App\Models\CalendarEvent;
use App\Models\EventInstance;
use Composer\Util\Http\Response;
use DateTime;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class EventsController extends Controller implements EventParameterInterface
{

    private const DEFAULT_LIMIT = 25;
    private const DEFAULT_CATEGORIES = ['socials'];

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

        $cities = $request->input(self::PARAMETER_CITY);
        $calendars = $request->input(self::PARAMETER_CALENDAR);
        $categories = $request->input(self::PARAMETER_CATEGORY);
        $weekdays = $request->input(self::PARAMETER_WEEKDAY);

        if (!$categories && !$calendars) {
            $categories = self::DEFAULT_CATEGORIES;
        }

        $ids = $this->getIdsFromSearchRequest($request);

        $startDate = $request->input(self::PARAMETER_FROM);
        if ($startDate === null) {
            $startDate = (new DateTime('today'))->setTime(0, 0, 0);
        } else {
            $startDate = (new DateTime($startDate));
        }

        $endDate = $request->input(self::PARAMETER_TO);
        if ($endDate) {
            $endDate = (new DateTime($endDate))->setTime(23, 59, 59);
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
        ;

        if ($endDate !== null) {
            $query->whereDate('end_date_time', '<=', $endDate->format(EventInstance::DATE_TIME_FORMAT_DB));
        }

        if ($skip > 0) {
            $query->skip($skip);
        }

        if (count($ids) > 0) {
            $query->whereIn('event_instances.id', $ids);
        }

        if (count($weekdays)) {
            $query->whereIn('weekday', $weekdays);
        }

        $query->limit($limit);

        $query->orderBy('start_date_time', 'ASC');

        return $query->get();
    }

    protected function getIdsFromSearchRequest(Request $request): ?array
    {
        $ids = [];
        foreach ((array) $request->get(self::PARAMETER_ID) as $v) {
            $ids[] = (int) $v;
        }
        return $ids;
    }

    public function searchEvents(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->validateRequest($request);
        $ids = $this->getIdsFromSearchRequest($request);
        return response()->json(
            (new EventInstance())
            ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id')
            ->whereIn('event_instances.id', $ids)->get()
        );
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
            'count' => [
                'search' => $request->input(self::PARAMETER_LIMIT) ?? self::DEFAULT_LIMIT,
                'result' => count($danceEvents)
            ],
            'dates'         => $dates,
            'danceEvents'   => $danceEvents
        ]);
    }
}
