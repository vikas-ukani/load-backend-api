<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class WeekWiseWorkout extends Model
{
    protected $table = "week_wise_workouts";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', // name of workouts
        'workout', // workout number
        'note', // Notes
        'THR', // THR
        'week_wise_frequency_master_id', // Week wise master
        'week_wise_frequency_master_ids', // Week wise master ids
        'training_activity_id', //  
        'training_goal_id', //  
        'training_intensity_id', //  
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
            'name' => 'required',
            'workout' => 'required|numeric',
            'week_wise_frequency_master_id' => 'required|numeric',
            'training_activity_id' => 'required|numeric',
            'training_goal_id' => 'required|numeric',
            'training_intensity_id' => 'required|numeric',
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
     * setWorkoutsAttribute => convert array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setWorkoutsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['workouts'] =  $value;
    }

    /**
     * getWorkoutsAttribute => convert string  to Array
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getWorkoutsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['workouts'] = $value;
    }
    /**
     * setWeekWiseFrequencyMasterIdsAttribute => convert array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setWeekWiseFrequencyMasterIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['week_wise_frequency_master_ids'] = $value;
    }

    /**
     * getWeekWiseFrequencyMasterIdsAttribute => convert string  to Array
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getWeekWiseFrequencyMasterIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['week_wise_frequency_master_ids'] = $value;
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

    /**
     * week_wise_frequency_master_detail => RELATION TO ONE
     *
     * @return void
     */
    public function week_wise_frequency_master_detail()
    {
        return $this->hasOne(WeekWiseFrequencyMaster::class, 'id', 'week_wise_frequency_master_id');
    }
    /**
     * training_activity_detail => RELATION TO ONE
     *
     * @return void
     */
    public function training_activity_detail()
    {
        return $this->hasOne(TrainingActivity::class, 'id', 'training_activity_id');
    }
    /**
     * training_goal_detail => RELATION TO ONE
     *
     * @return void
     */
    public function training_goal_detail()
    {
        return $this->hasOne(TrainingGoal::class, 'id', 'training_goal_id');
    }
    /**
     * training_intensity_detail => RELATION TO ONE
     *
     * @return void
     */
    public function training_intensity_detail()
    {
        return $this->hasOne(TrainingIntensity::class, 'id', 'training_intensity_id');
    }

    public function week_wise_workout_laps_details()
    {
        return $this->hasMany(WorkoutWiseLap::class, 'week_wise_workout_id', 'id');
    }
}
