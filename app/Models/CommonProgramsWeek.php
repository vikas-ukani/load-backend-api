<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CommonProgramsWeek extends Model
{

    /**
     * table name
     *
     * @var string
     */
    protected $table = "common_programs_weeks";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'training_activity_id', //  
        'training_goal_id', //   
        'training_intensity_id', //  
        'thr', //  
        'name',
        'note',
        'sequence', // 
        'is_active', //  
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
            'training_activity_id' => $once . 'required',
            'training_goal_id' => $once . 'required',
            'training_intensity_id' => $once . 'required',
            'thr' => $once . 'required',
            'name' => $once . 'required',
            'note' => $once . 'required',
            'is_active' => $once . 'required',
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

    public function training_activity_detail()
    {
        return $this->hasOne(TrainingActivity::class, 'id', 'training_activity_id');
    }

    public function training_goal_detail()
    {
        return $this->hasOne(TrainingGoal::class, 'id', 'training_goal_id');
    }

    public function training_intensity_detail()
    {
        return $this->hasOne(TrainingIntensity::class, 'id', 'training_intensity_id');
    }

    public function common_programs_weeks_laps_details()
    {
        return $this->hasMany(CommonProgramsWeeksLaps::class, 'common_programs_week_id', 'id');
    }
}
