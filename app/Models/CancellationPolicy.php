<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CancellationPolicy extends Model
{
    protected $table = "cancellation_policies";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', // cancellation policies name
        'code', // cancellation policies code generate from name
        'description', // cancellation policies code generate from name
        'is_active', // cancellation policies is active or not
    ];



    /**
     * setNameAttribute => set name to Title case
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
            'name' =>  $once . 'required|max:200',
            'code' =>  $once . 'required|unique:cancellation_policies,code,' . $id,
            'description' =>  $once . 'required',
            'is_active' =>  $once . 'required',
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
     * @return \Illuminate\Contracts\Validation\Validator
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
        return $this->attributes['is_active'] = $value === 1  ? true : false;
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
