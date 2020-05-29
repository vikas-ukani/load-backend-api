<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class TrainingIntensity extends Model
{

    protected $table = "training_intensity";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', // store name
        'code', // code from name
        'sequence', // sequence wise listing
        'target_hr', // targeted hr 
        'is_active', // Account is active or deactivate
    ];

    /**
     * setNameAttribute => Set Name to  Toggle Case
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords(strtolower($value));
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
        $once = "sometimes|";
        $rules = [
            'name' => $once . 'required|max:100',
            'code' => $once . 'required|unique:training_intensity,code,' . $id,
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
        return Validator::make($input, TrainingIntensity::rules($id), TrainingIntensity::messages());
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
