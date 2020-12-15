<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Google_Service_Calendar_EventDateTime;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter;

/**
 * App\Models\CalendarEvent
 *
 * @property string $id
 * @property string $summary
 * @property string|null $description
 * @property Carbon $start_date_time
 * @property Carbon $end_date_time
 * @property string $updated
 * @property string $created
 * @property string $creator
 * @property string $calendar
 * @property string|null $location
 * @property string|null $category
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $city
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereCalendar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereCreator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereEndDateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereStartDateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CalendarEvent whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string $start_date_time
 * @property string $end_date_time
 */
class CalendarEvent extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    public const DATE_TIME_FORMAT_DB = 'Y-m-d H:i:s';
    public const DATE_TIME_FORMAT_JS = 'Y-m-d\TH:i:sP';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var mixed[]
     */
    protected $fillable = [
        'calendar',
        'category',
        'city',
        'created',
        'creator',
        'description',
        'end_date_time',
        'event_id',
        'id',
        'location',
        'start_date_time',
        'summary',
        'updated',
    ];

    protected $dates = [
        'start_date_time',
        'end_date_time',
        'created',
        'updated'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    private function unfuckDate(Google_Service_Calendar_EventDateTime $value): string
    {

        $val = $value->getDateTime() ?? $value->getDate();
        $tz = $value->getTimeZone();

        return (Carbon::parse($val, $tz))->format(self::DATE_TIME_FORMAT_DB);

    }

    public function setStartDateTimeAttribute(Google_Service_Calendar_EventDateTime $value): void
    {
        $this->attributes['start_date_time'] = $this->unfuckDate($value);
    }

    public function getStartDateTimeAttribute(string $start_date_time): string
    {
        return (new \DateTime($start_date_time))->format(self::DATE_TIME_FORMAT_JS);
    }

    public function setEndDateTimeAttribute(Google_Service_Calendar_EventDateTime $value): void
    {
        $this->attributes['end_date_time'] = $this->unfuckDate($value);
    }

    public function getEndDateTimeAttribute(string $end_date_time): string
    {
        return (new \DateTime($end_date_time))->format(self::DATE_TIME_FORMAT_JS);
    }

    public function setUpdatedAttribute(string $updated): void
    {
        $this->attributes['updated'] = (Carbon::parse($updated))->format(self::DATE_TIME_FORMAT_DB);
    }

    public function setCreatedAttribute(string $created): void
    {
        $this->attributes['created'] = (Carbon::parse($created))->format(self::DATE_TIME_FORMAT_DB);
    }

}
