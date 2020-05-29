<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class RepetitionMax extends Model
{
    protected $table = "repetition_maxes";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', // Mechanics name
        'code', // Mechanics code generate from name
        'weight', // Mechanics code generate from name
        // 'estimated_weight', // Mechanics code generate from name
        // 'actual_weight', // Mechanics code generate from name
        'is_active', // Mechanics is active or not
    ];

    /**
     * validation => Check Validation
     *
     * @param mixed $input
     * @param mixed $id
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, self::rules($id), self::messages());
    }

    /**
     * rules => set validation rules
     *
     * @param mixed $id
     *
     * @return array
     */
    public static function rules($id)
    {
        $once = isset($id) ? 'sometimes|' : '';
        return [
            'name' => $once . 'required|max:200',
            'code' => $once . 'required|unique:repetition_maxes,code,' . $id,
            'weight' => $once . 'required',
            'is_active' => $once . 'required',
        ];
    }

    /**
     * messages => Set Error Message
     *
     * @return array
     */
    public static function messages()
    {
        return [
            'required' => __('validation.required'),
        ];
    }

    /**
     * setNameAttribute => set name to Title case
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords(strtolower($value));
    }

    /**
     * scopeOrdered =>default sorting on created at as ascending
     *
     * @param mixed $query
     *
     * @return void
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
