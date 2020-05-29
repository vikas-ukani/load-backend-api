<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class TrainingFrequencies extends Model
{

    protected $table = "training_frequencies";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', // title
        'code', // code unique
        'max_days', // max_days for validation from app
        'preset_training_program_ids', // To show preset program ids
        'is_active',  // Frequency is active or deactivate
        'created_at',
        "updated_at"
    ];

    /**
     * setTitleAttribute => set title value
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = ucwords(strtolower($value));
    }

    /**
     * getIsActiveAttribute
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getIsActiveAttribute($value)
    {
        return $this->attributes['is_active'] =  $value === 1  ? true : false;
    }

    /**
     * Set Validation Rules
     */
    public static function rules($id)
    {
        $rules = [
            'title' => 'required|max:200',
            'code' => 'required|unique:training_frequencies,code,' . $id,
            'is_active' => 'required',
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
        return Validator::make($input, TrainingFrequencies::rules($id), TrainingFrequencies::messages());
    }

    /**
     * default sorting on created at as ascending 
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // preset_training_program_ids

    /**
     * setPresetTrainingProgramIdsAttribute => convert array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setPresetTrainingProgramIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['preset_training_program_ids'] =  $value;
    }

    /**
     * getPresetTrainingProgramIdsAttribute => convert string  to Array
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getPresetTrainingProgramIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['preset_training_program_ids'] = $value;
    }



    /**
     * History Relation
     */
    // public function history_details()
    // {
    //     return $this->hasMany("App\Models\History", 'user_id', 'id');
    // }

}
