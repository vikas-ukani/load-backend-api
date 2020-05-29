<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class SavedWorkout extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "training_log_id",
        "user_id"
    ];

    /**
     * set Validation Rules
     */
    public static function rules($input, $id = null)
    {
        $rules = [
            "training_log_id"  => 'required',
            "user_id"  => 'required',

            // "training_activity_id" => 'required',  
        ];
        return $rules;
    }

    /**
     *
     * Set Error Message
     */
    public static function messages()
    {
        /** set error message in trans files */
        return [
            'required' => __('validation.required'),
        ];
    }

    /**
     * Check Validation
     */
    public static function validation($input, $id = null)
    {
        $className = __CLASS__;
        return Validator::make($input, $className::rules($input, $id), $className::messages());
    }

    /**
     * default sorting on created at as ascending 
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * user_details => Many user detail
     *
     * @return void
     */
    public function user_detail()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * training_logs => Many Training Logs detail
     *
     * @return void
     */
    public function training_log()
    {
        return $this->hasOne(TrainingLog::class, 'id', 'training_log_id');
    }
}
