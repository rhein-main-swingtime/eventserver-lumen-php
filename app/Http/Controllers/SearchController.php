<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EventInstance;
use Illuminate\Http\Request;

class SearchController extends Controller
{

    /**
     * Shows Events
     */
    public function runTextSearch(Request $request, string $search = '', int $limit = 0)
    {

        if (\strlen($search) < 3){
            response()->json(['result' => null]);
        }

        $search = \urldecode($search);

        $query = EventInstance::where('description', 'like', "%{$search}%")
            ->orWhere('summary', 'like', "%{$search}%")
            ->where('endDateTime', '>', (new \DateTimeImmutable())->format(EventInstance::DATE_TIME_FORMAT_DB));

        if ($limit > 0) {
            $query->limit($limit);
        }

        return response()->json(
            [
                 'result' => $query->get(),
            ]
        );

    }
}
