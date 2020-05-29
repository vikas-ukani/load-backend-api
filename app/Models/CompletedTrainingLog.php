<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompletedTrainingLog extends Model
{
    /**
     * table name
     *
     * @var string
     */
    protected $table = "completed_training_logs";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "exercise", // store in json
        "training_log_id", // from training log module
    ];

    /**
     * setExerciseAttribute => store in json data
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setExerciseAttribute($value)
    {
        $this->attributes['exercise'] = json_encode($value);
    }

    /**
     * getExerciseAttribute => convert string to object when get data from database
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getExerciseAttribute($value)
    {
        return $this->attributes['exercise'] = json_decode($value, true); // true for get in array form
    }

    /**
     * set Validation Rules
     * @param $input
     * @param null $id
     * @return array
     */
    public static function rules($input, $id = null)
    {
        return [
            // "training_log_id" => 'required',
        ];
    }

    /**
     *
     * Set Error Message
     */
    public static function messages()
    {
        return [
            'required' => __('validation.required'),
        ];
    }

    /**
     * Check Validation
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, CompletedTrainingLog::rules($input, $id), CompletedTrainingLog::messages());
    }

    /**
     * default sorting on created at as ascending
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * training_log_detail => relation with template logs
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function training_log_detail()
    {
        return $this->hasOne(TrainingLog::class, 'id', 'training_log_id');
    }
}
