<?php

namespace App\Models;

use App\Models\TrainingGoal;
use App\Models\TrainingIntensity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class LogCardioValidations extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'training_activity_id', // coming from training activity table
        'training_goal_id', // coming from training goal table
        'distance_range', // distance_range range
        'duration_range', // duration_range range
        'speed_range', // speed_range range
        'pace_range', // pace_range range
        'percentage_range', // percentage_range range
        'rest_range', // rest_range range
        'is_active', // check for active validation or not.
    ];

    protected $casts = [
        'is_active'                     =>  'boolean',
        'training_activity_id'          =>  'integer',
        'training_goal_id'              =>  'integer',
    ];
 
    /**
     * rules => set validation rules
     *
     * @return array
     */
    public static function rules()
    {
        return [
            //            'name' => 'required|max:200',
            'is_active' => 'required',
        ];
    }

    /**
     * messages => Set Error Message
     *
     * @return array
     */
    public static function messages()
    {
        /** set error message in trans files */
        return [
            'required' => __('validation.required'),
        ];
    }

    /** Check Validation
     * @param $input
     * @param null $id
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, self::rules($id), self::messages());
    }

    /**
     * scopeOrdered => default sorting on created at as ascending
     *
     * @param mixed $query
     *
     * @return void
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    protected static function boot()
    {
        parent::boot();

        // static::creating(function ($model) {
            //            $model->name = ucwords(strtolower($value));
        // });

        // static::updating(function ($model) {
            //            $model->name = ucwords(strtolower($value));
        // });
    }
  
}
