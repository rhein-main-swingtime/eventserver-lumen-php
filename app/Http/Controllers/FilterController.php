<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\EventInstance;
use Carbon\Carbon;
use DateTime;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilterController extends Controller
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

    protected function validateRequest(Request $request): void
    {
        $this->validate($request, self::PARAMETER_VALIDATIONS);
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

    protected function getAvailableFilters(): array {
        $out = [];

        foreach (self::PARAMETERS as $cat) {
            if ($cat === self::PARAMETER_CITY) {
                $distinct = EventInstance::distinct($cat);
            } else {
                $distinct = CalendarEvent::distinct($cat);
            }

            $out[$cat] = $distinct->pluck($cat)
                ->toArray();
        }

        return $out;
    }

    /**
     * Returns filters as array
     *
     * @return array
     */
    public function fetchFilters(Request $request): array
    {
        $out = $this->getAvailableFilters($request);
        return $out;
    }

    public function getCount(string $version, string $category, string $name, Request $request): int {

        $this->validateRequest($request);

        $category = urldecode($category);
        $name = urldecode($name);

        $instance = (new EventInstance())::query()
        ->join('calendar_events', 'calendar_events.event_id', '=', 'event_instances.event_id')
        ->whereDate('end_date_time', '>=', Carbon::now()->toDateTimeString())
        ->select([$category])->where($category, $name);

        foreach (self::PARAMETERS as $param) {
            $inputs = $request->input($param);
            if ($inputs === null || !is_array($inputs)) {
                continue;
            }

            foreach($inputs as $input) {
                if ($param === $category) {
                    continue;
                }
                $instance->where($param, $input);
            }
        }

        return $instance->get()->count();
    }
}
