<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\EventInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterController extends Controller
{

    public const PARAMETER_CITY         = 'city';
    public const PARAMETER_CATEGORY     = 'category';
    public const PARAMETER_CALENDAR     = 'calendar';

    public const PARAMETERS = [
        self::PARAMETER_CALENDAR,
        self::PARAMETER_CATEGORY,
        self::PARAMETER_CITY
    ];

    private const PARAMETER_VALIDATIONS= [
        self::PARAMETER_CALENDAR => 'array',
        self::PARAMETER_CATEGORY => 'array',
        self::PARAMETER_CITY => 'array',
    ];

    protected Request $request;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->validate($request, self::PARAMETER_VALIDATIONS);
        $this->request = $request;
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
            ->whereDate('end_date_time', '>=', Carbon::now()->toDateTimeString())
            ->count();

            $out[$category] = [
                'count' => $count,
                'selected' => in_array($category, $selected)
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

    protected function getAvailableFilters(Request $request): array
    {
        $filters = [];

        foreach (self::PARAMETERS as $cat) {
            if ($cat === self::PARAMETER_CITY) {
                $distinct = EventInstance::distinct($cat);
            } else {
                $distinct = CalendarEvent::distinct($cat);
            }

            $filters[$cat] = [];
            $items = $distinct->pluck($cat)->toArray();
            $queryItems = $request->input($cat) ?? [];

            foreach ($items as $item) {
                $builder = $this->generateBaseCollection();
                if (in_array($item, $queryItems)) {
                    $count = $builder->getQuery()->count();
                } elseif (empty($queryItems) !==  true) {
                    $count = $builder->orWhere($cat, $item)->getQuery()->count();
                } else {
                    $count = $builder->where($cat, $item)->getQuery()->count();
                }

                $filters[$cat][] = [
                    'name' => $item,
                    'available' => $count
                ];
            }
        }

        return [
            'filters' => $filters,
            'totalCount' => $this->generateBaseCollection()->get()->count(),
        ];
    }

    protected function generateBaseCollection(): \Illuminate\Database\Eloquent\Builder
    {

        $instance = (new EventInstance())::query()
        ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id')
        ->whereDate('end_date_time', '>=', Carbon::now()->toDateTimeString());

        foreach (self::PARAMETERS as $param) {
            $inputs = $this->request->input($param);
            if ($inputs === null || !is_array($inputs)) {
                continue;
            }

            $instance->whereIn($param, $inputs);
        }

        return $instance;
    }

    /**
     * Returns filters as array
     *
     * @return JsonResponse
     */
    public function fetchFilters(): JsonResponse
    {
        $out = $this->getAvailableFilters($this->request);
        return response()->json($out);
    }
}
