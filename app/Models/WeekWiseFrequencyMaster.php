<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class WeekWiseFrequencyMaster extends Model
{
    protected $table = "week_wise_frequency_masters";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'training_plan_type', // training plan types like 5K, 10K, 21K and 42K
        'week_number', // week number
        'frequency', // frequency number X
        'workouts', // week workouts W1,W2,W3
        'base', // week workouts W1,W2,W3
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
            'training_plan_type' => 'required',
            'week_number' => 'required|numeric',
            'frequency' => 'required|numeric',
            'workouts' => 'required|array',
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

    public function week_wise_frequency_master_details()
    {
        return $this->hasMany(WeekWiseWorkout::class, 'week_wise_frequency_master_id', 'id');
    }
}
