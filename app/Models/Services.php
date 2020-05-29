<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Services extends Model
{
    protected $table = "services";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

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
            'code' => 'required|unique:services,code,' . $id,
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
        return Validator::make($input, Services::rules($id), Services::messages());
    }

    /**
     * setNameAttribute => Set name to capitalize
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
