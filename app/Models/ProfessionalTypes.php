<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class ProfessionalTypes extends Model
{
    protected $table = "professional_types";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', // Name of your payment option
        'code', // code for unique payment option type
        'description', // for more description payment options
        'is_active', // for active or de-active
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
        // $this->attributes['name'] = ucwords(strtolower($value));
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
            'name' => 'required|max:100',
            'code' => 'required|unique:professional_types,code,' . $id,
            'is_active' => 'required',
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
}
