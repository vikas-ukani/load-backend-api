<?php

namespace App\Models;

use App\Supports\DateConvertor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoadCenterEvent extends Model
{
    use DateConvertor;

    protected $table = "load_center_events";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // 1 screen
        'user_id', // who create this event
        'event_type_ids', // who create this event
        // 'title', // title of event
        'visible_to', // visible to LOAD_CENTER_EVENT_VISIBILITY_INVITATION_ONLY and LOAD_CENTER_EVENT_VISIBILITY_PUBLIC use constant here
        'max_guests', // number of guests will come
        // 2 screen
        'event_name', // name of event
        'event_image', // event image to show in list
        'date_time', // event date
        // 'time', // event time
        'earlier_time', // to come earlier time
        'duration', // event durations
        'location', // event location
        // 'location_map', // event location on map  { "lat" : 1, "long" : 1 } ,
        'latitude', // event latitude on map  12.01531321.
        'longitude', // event longitude on map 41.151321231
        // 3 screen
        'description', //  more about event description
        'amenities_available', // event services like { "drinking_water" : true, towel : false, locker : true }
        // 4 Screen
        'event_price', // event price
        'currency_id', // price currency id
        // 5 Screen
        'cancellation_policy_id', // cancellation policy
        'general_rules', // rules for events
    ];

    protected $appends = ['is_bookmarked'];

    /**
     * rules => set validation rules
     *
     * @param  mixed $id
     *
     * @return array
     */
    public static function rules($id)
    {
        $once = isset($id) ? 'sometimes|' : '';

        return [
            // 1.
            // 'title' => $once . 'required|max:200',
            'user_id' => $once . 'required|integer',
            'event_type_ids' => $once . 'required',
            'visible_to' => $once . 'required',
            // 2.
            'event_name' => $once . 'required|max:200',
            'event_price' => $once . 'required|integer',
            'currency_id' => $once . 'required|integer',

            /** REVIEW  Uncomment for image line */
            'event_image' => $once . 'required',
            'date_time' => $once . 'required|after:today',
            // 'time' => $once . 'required',
            'duration' => $once . 'required|integer',
            'location' => $once . 'required',
            'latitude' => $once . 'between:0.0,9999999999999999.99',
            'latitude' => $once . 'between:0.0,9999999999999999.99',
            'max_guests' => $once . 'required',
            'cancellation_policy_id' => $once . 'required',
            'general_rules' => $once . 'required',
        ];
    }

    /**
     * setDateTimeAttribute => set date
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setDateTimeAttribute($value)
    {
        $this->attributes['date_time'] = $this->isoToUTCFormat($value);
    }

    /**
     * setTitleAttribute => set title to capital first
     *
     * @param  mixed $value
     *
     * @return void
     */
    // public function setTitleAttribute($value)
    // {
    //     $this->attributes['title'] = ucwords(strtolower($value));
    // }

    /**
     * setEventNameAttribute => set event name to capital first
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setEventNameAttribute($value)
    {
        $this->attributes['event_name'] = ucwords(strtolower($value));
    }

    /**
     * setAmenitiesAvailableAttribute => convert to json
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setAmenitiesAvailableAttribute($value)
    {
        $value = is_string($value) ? $value : json_encode($value);
        $this->attributes['amenities_available'] = $value;
    }

    /**
     * getAmenitiesAvailableAttribute => json to real
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getAmenitiesAvailableAttribute($value)
    {
        return $this->attributes['amenities_available'] = is_string($value) ? json_decode($value, true) : $value;
    }
    public function getEventTypeIdsAttribute($value)
    {
        return $this->attributes['event_type_ids'] = is_string($value) ? json_decode($value, true) : $value;
    }

    /**
     * setLocationMapAttribute => store in json data
     *
     * @param  mixed $value
     *
     * @return void
     */
    // public function setLocationMapAttribute($value)
    // {
    //     $value = is_string($value) ? $value : json_encode($value);
    //     $this->attributes['location_map'] = $value;
    // }

    /**
     * getLocationMapAttribute => convert string to object when get data from database
     *
     * @param  mixed $value
     *
     * @return void
     */
    // public function getLocationMapAttribute($value)
    // {
    //      return $this->attributes['location_map'] = is_string($value) ? json_decode($value, true) : $value;
    // }

    /**
     * getDurationAttribute => return with integer number
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getDurationAttribute($value)
    {
        return $this->attributes['duration'] = (int) $value; // true for get in array form
    }

    /**
     * getEventImageAttribute => append base url to event_image with unique
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getEventImageAttribute($value)
    {
        $this->attributes['event_image'] = env('APP_URL',  url('/')) . $value;
        $arr = array_unique(explode(env('APP_URL',  url('/')), $this->attributes['event_image']));
        return $this->attributes['event_image'] = implode(env('APP_URL',  url('/')), $arr);
    }

    public function getIsBookmarkedAttribute()
    {
        return !!Bookmark::where('event_id', $this->attributes['id'])->where('user_id', Auth::id())->count();
    }

    /**
     * messages => Set Error Message
     *
     * @return void
     */
    public static function messages()
    {
        return [
            // 'required' => __('validation.required'),
        ];
    }

    /**
     * validation => Check Validation
     *
     * @param  mixed $input
     * @param  mixed $id
     *
     * @return object
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, self::rules($id), self::messages());
    }

    /**
     * scopeOrdered =>default sorting on created at as ascending
     *
     * @param  mixed $query
     *
     * @return void
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * user_detail => relation with user details
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user_detail()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * currency_detail => relation with currency
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currency_detail()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    /**
     * cancellation_policy_detail => relation with cancellation policy
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cancellation_policy_detail()
    {
        return $this->hasOne(CancellationPolicy::class, 'id', 'cancellation_policy_id');
    }

    // public function event_type_detail()
    // {
    //     return $this->hasOne(EventType::class, 'id', 'event_type_id');
    // }
}
