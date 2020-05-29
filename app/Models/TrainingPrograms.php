<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class TrainingPrograms extends Model
{
    /** table name */
    protected $table = "training_programs";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',  // RESISTANCE and CARDIO Use Constant	
        'type',  // PRESET and CUSTOM Use Constant
        'user_id',  // which user add this training program
        'preset_training_programs_id', // from training preset program table
        'training_frequencies_id', // from training frequencies table
        'days', // multiple days

        'start_date', // start date
        'end_date', // end date
        'by_date', // START, END => use constant
        // 'date', // date

        "phases", /// store array object when TYPE is CUSTOM
        'created_at', // when creating 
        "updated_at" // when updating
    ];

    /** return UTC date */
    protected $dates = [
        'date',
    ];

    /**
     * setPhasesAttribute => when store in the database set to sting format
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setPhasesAttribute($value)
    {
        $this->attributes['phases'] = json_encode($value);
    }

    /**
     * getPhasesAttribute => convert string to object when get data from database
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getPhasesAttribute($value)
    {
        return $this->attributes['phases'] = json_decode($value, true); // true for get in array form
    }

    /**
     * setDaysAttribute => convert array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setDaysAttribute($value)
    {
        if (is_array($value)) $this->attributes['days'] = strtoupper(implode(',', $value));
    }

    /**
     * getDaysAttribute  => convert string to array
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getDaysAttribute($value)
    {
        //    if (is_string($value))  return explode(',', $value); // // Original
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['days'] = (isset($value) && count($value)) ?  $value : null;
    }

    /**
     * rules => Set Validation Rules
     *
     * @param  mixed $id
     * @param  mixed $input
     *
     * @return void
     */
    public static function rules($id, $input = null)
    {
        $rules = [];
        /** status wise validation rule applied */
        if (isset($input) && $input['status'] && $input['type']) {
            if (in_array($input['status'], [TRAINING_PROGRAM_STATUS_RESISTANCE, TRAINING_PROGRAM_STATUS_CARDIO]) && $input['type'] == TRAINING_PROGRAM_TYPE_PRESET) {
                $rules['user_id'] = 'required|integer';
                $rules['preset_training_programs_id'] = 'required';
                // $rules['by_date'] = 'required';

                $rules['start_date'] = 'required';
                $rules['end_date'] = 'required';
                // $rules['date'] = 'required';
                $rules['training_frequencies_id'] = 'required';
                $rules['days'] = 'required';
            } elseif ($input['status'] == TRAINING_PROGRAM_STATUS_RESISTANCE && $input['type'] == TRAINING_PROGRAM_TYPE_CUSTOM) {

                $rules['user_id'] = 'required';
                $rules['phases'] = 'required|array';
                /* } elseif ($input['status'] == TRAINING_PROGRAM_STATUS_CARDIO && $input['type'] == TRAINING_PROGRAM_TYPE_PRESET) {
            # code... */
            } elseif ($input['status'] == TRAINING_PROGRAM_STATUS_CARDIO && $input['type'] == TRAINING_PROGRAM_TYPE_CUSTOM) {
                # pass
            }
        }
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
        return Validator::make($input, TrainingPrograms::rules($id, $input), TrainingPrograms::messages());
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
     * preset_training_program => relation with PRESET training programs
     * 
     * @return void
     */
    public function preset_training_program()
    {
        return $this->hasOne("App\Models\PresetTrainingProgram", 'id', 'preset_training_programs_id');
    }

    /** 
     * training_frequency => relation with Training Frequency
     *
     * @return void
     */
    public function training_frequency()
    {
        return $this->hasOne("App\Models\TrainingFrequencies", 'id', 'training_frequencies_id');
    }

    /**
     * user_detail => Relation with Users
     *
     * @return void
     */
    public function user_detail()
    {
        return $this->hasOne("App\Models\User", 'id', 'user_id');
    }
}
