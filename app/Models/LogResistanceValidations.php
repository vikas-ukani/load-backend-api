<?php

/** @noinspection ALL */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class LogResistanceValidations extends Model
{

    /** @var string */
    protected $table = "log_resistance_validations";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'training_intensity_id', // coming from training intensity table
        'training_goal_id', // coming from training goal table
        'weight_range', // Weight range
        'reps_range', // Reps range
        'reps_time_range', // Reps time range
        'rest_range', // Rest range
        'is_active', // check for active validation or not.
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

        static::creating(function ($model) {
            //            $model->name = ucwords(strtolower($value));
        });

        static::updating(function ($model) {
            //            $model->name = ucwords(strtolower($value));
        });
    }

    public function training_intensity_detail()
    {
        return $this->hasOne(TrainingIntensity::class, 'id', 'training_intensity_id');
    }

    public function training_goal_detail()
    {
        return $this->hasOne(TrainingGoal::class, 'id', 'training_goal_id');
    }
}
