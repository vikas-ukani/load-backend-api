<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class PresetTrainingProgram extends Model
{

    // table name
    protected $table = "preset_training_programs";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', // program title name
        'code', // program code name
        'subtitle', // program subtitle name
        'status', // Status Use constant RESISTANCE and CARDIO
        'type', // use constant PRESET and CUSTOM
        'is_active', // active or not
        'is_active', // active or not
        'weeks', // use weeks for  use date from CARDIO
        'created_at',
        'updated_at',
    ];

    /**
     * setTitleAttribute => To Set First Letter Caps
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = ucfirst(strtolower($value));
    }

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
            'title' => 'required|max:200',
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
        return Validator::make($input, PresetTrainingProgram::rules($id), PresetTrainingProgram::messages());
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
     * History Relation
     */
    // public function history_details()
    // {
    //     return $this->hasMany("App\Models\History", 'user_id', 'id');
    // }
}
