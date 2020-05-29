<?php

namespace App\Models;

use App\Models\WeekWiseWorkout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class WorkoutWiseLap extends Model
{
    protected $table = "workout_wise_laps";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // 'week_wise_workout_id', // name of workouts
        'week_wise_workout_ids', // name of workouts
        'lap', // lap number
        'percent', // percent of current lap
        'distance', // distance of current lap
        'duration', // duration of current lap
        'speed', // speed of current lap
        'rest', // rest of current lap
        'VDOT', // VDOT for calculate speed
    ];

    /**
     * rules => set Validation Rules
     *
     * @param  mixed $id
     *
     * @return void
     */
    public static function rules($id)
    {
        $rules = [
            // 'week_wise_workout_id' => 'required|numeric',
            'week_wise_workout_ids' => 'required',
            'lap' => 'required',
            'percent' => 'required',
            'distance' => 'required',
            'duration' => 'required',
            'speed' => 'required',
            'rest' => 'required',
            'VDOT' => 'required',

        ];
        return $rules;
    }

    /**
     * messages => Set Error Message
     *
     * @return void
     */
    public static function messages()
    {
        /** set error message in trans files */
        return [
            'required' => __('validation.required'),
        ];
    }

    /**
     * validation => Check Validation
     *
     * @param  mixed $input
     * @param  mixed $id
     *
     * @return void
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, self::rules($id), self::messages());
    }

    /**
     * scopeOrdered => default sorting on created at as ascending
     *
     * @param  mixed $query
     *
     * @return void
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /** week_wise_workout_ids
     * setWeekWiseWorkoutIdsAttribute => convert array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setWeekWiseWorkoutIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['week_wise_workout_ids'] =  $value;
    }

    /**
     * getWeekWiseWorkoutIdsAttribute => convert string  to Array
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getWeekWiseWorkoutIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['week_wise_workout_ids'] = $value;
    }

    /**
     * week_wise_frequency_master_detail => RELATION TO ONE
     *
     * @return void
     */
    public function week_wise_workout_detail()
    {
        return $this->hasOne(WeekWiseWorkout::class, 'id', 'week_wise_workout_id');
    }
}
