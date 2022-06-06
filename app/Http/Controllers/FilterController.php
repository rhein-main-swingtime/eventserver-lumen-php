<?php

namespace App\Http\Controllers;

use App\Http\Parameter\EventParameterInterface;
use App\Models\CalendarEvent;
use App\Models\EventInstance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @todo Add Dates and pure counts for updates in frontend
 */
class FilterController extends Controller implements EventParameterInterface
{
    /**
     * Returns Categories from DB.
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
            ->whereDate('end_date_time', '>=', Carbon::now()->toDateTimeString())
            ->count();

            $out[$category] = [
                'count' => $count,
                'selected' => in_array($category, $selected),
            ];
        }

        return $out;
    }

    protected function checkIsSelected(
        Request $request,
        string $category,
        array $values
    ): array {
        $selectedByQuery = $request->input($category);
        if ($selectedByQuery === null || count($selectedByQuery) === 0) {
            return $values;
        }
    }

    protected function sortFilters(array $filters): array
    {
        uasort($filters, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $filters;
    }

    protected function getAvailableFilters(Request $request): array
    {
        $filters = [];

        foreach (self::FILTER_PARAMETERS as $cat) {
            if ($cat === self::PARAMETER_CITY) {
                $distinct = EventInstance::distinct($cat);
            } else {
                $distinct = CalendarEvent::distinct($cat);
            }

            $filters[$cat] = [];
            $items = $distinct->pluck($cat)->toArray() ?? [];
            sort($items);
            $queryItems = $request->input($cat) ?? [];

            foreach ($items as $item) {
                $builder = $this->generateBaseCollection($request);
                if (in_array($item, $queryItems)) {
                    $count = $builder->getQuery()->count();
                } elseif (empty($queryItems) !== true) {
                    $count = $builder->orWhere($cat, $item)->getQuery()->count();
                } else {
                    $count = $builder->where($cat, $item)->getQuery()->count();
                }

                $filters[$cat][] = [
                    'name' => $item,
                    'available' => $count,
                ];
            }
        }

        return [
            'filters' => $filters,
            'totalCount' => $this->generateBaseCollection($request)->get()->count(),
        ];
    }

    protected function generateBaseCollection(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $instance = (new EventInstance())::query()
        ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id')
        ->whereDate(
            'end_date_time',
            '>=',
            Carbon::now()->toDateTimeString()
        );

        if ($request->get(self::PARAMETER_TO) !== null) {
            $instance->whereDate('end_date_time', '<=', $request->get(self::PARAMETER_TO));
        }

        foreach (self::FILTER_PARAMETERS as $param) {
            $inputs = $request->input($param);
            if ($inputs === null || !is_array($inputs)) {
                continue;
            }

            $instance->whereIn($param, $inputs);
        }

        return $instance;
    }

    /**
     * Returns filters as array.
     */
    public function fetchFilters(Request $request): JsonResponse
    {
        $this->validate($request, self::VALIDATIONS);
        $out = $this->getAvailableFilters($request);

        return response()->json($out);
    }
}
