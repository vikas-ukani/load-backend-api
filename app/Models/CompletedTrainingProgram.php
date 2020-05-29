<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CompletedTrainingProgram extends Model
{
    /**
     * table name
     *
     * @var string
     */
    protected $table = "completed_training_programs";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'program_id', //  from training program id
        'common_programs_weeks_id', // from common 
        'week_wise_workout_id', // store workout id in this date 
        'exercise', // for set daily exercise
        'is_complete', // to check exercise is completed or not
        "date", // date of selected week of program.
    ];

    /**
     * rules => set validation rules
     *
     * @param  mixed $id
     *
     * @return void
     */
    public static function rules($id)
    {
        $once = isset($id) ? 'sometimes|' : '';
        $rules = [
            'program_id' =>  $once . 'required',
            'common_programs_weeks_id' =>  $once . 'required',
            // 'exercise' =>  $once . 'required', 
            'is_complete' =>  $once . 'required',
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
     * scopeOrdered => default sorting on created at as ascending.
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
     * setExerciseAttribute => When store in the database set to sting format
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
        return $this->attributes['exercise'] = (isset($value)) ? json_decode($value, true) : null; // true for get in array form
    }

    /**
     * setIsCompleteAttribute => convert to boolean
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setIsCompleteAttribute($value)
    {
        $this->attributes['is_complete'] = $value == true ? 1 : 0;
    }

    /**
     * getIsCompleteAttribute => convert number to boolean
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getIsCompleteAttribute($value)
    {
        return $this->attributes['is_complete'] = $value == 1 ? true : false;
    }

    /**
     * proggram_detail => hasOne relation with TrainingPrograms
     *
     * @return void
     */
    public function program_detail()
    {
        return $this->hasOne(TrainingPrograms::class, 'id', 'program_id');
    }

    /**
     * common_programs_weeks_detail => hasOne relation with CommonProgramsWeek
     *
     * @return void
     */
    public function common_programs_weeks_detail()
    {
        return $this->hasOne(CommonProgramsWeek::class, 'id', 'common_programs_weeks_id');
    }

    /**
     * common_programs_weeks_laps_details => relation with programs logs
     *
     * @return void
     */
    public function common_programs_weeks_laps_details()
    {
        return $this->hasMany(CommonProgramsWeeksLaps::class, 'common_programs_week_id', 'common_programs_weeks_id');
    }

    /**
     * week_wise_workout_detail => relation with wise workout
     *
     * @return void
     */
    public function week_wise_workout_detail()
    {
        return $this->hasOne(WeekWiseWorkout::class, 'id', 'week_wise_workout_id');
    }
}
