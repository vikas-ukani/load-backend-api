<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CommonProgramsWeeksLaps extends Model
{

    /**
     * table name
     *
     * @var string
     */
    protected $table = "common_programs_weeks_laps";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'common_programs_week_id', //
        'lap', //
        'percent', //
        'distance', //
        'speed',
        'rest',
        'vdot', //
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
            'common_programs_week_id' =>  $once . 'required',
            'lap' =>  $once . 'required',
            'percent' =>  $once . 'required',
            'distance' =>  $once . 'required',
            // 'speed' =>  $once . 'required',
            // 'rest' =>  $once . 'required',
            'vdot' =>  $once . 'required',
            'is_active' =>  $once . 'required',
        ];
        return $rules;
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
     * common_programs_week_detail => get details of common program  weeks
     *
     * @return void
     */
    public function common_programs_week_detail()
    {
        return $this->hasOne(CommonProgramsWeek::class, 'id', 'common_programs_week_id');
    }
}
