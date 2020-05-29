<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class TrainingSettingUnits extends Model
{

    protected $table = 'training_setting_units';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    /**
     * set Validation Rules
     * @param $input
     * @param null $id
     * @return array
     */
    public static function rules($input, $id = null): array
    {
        $once = isset($id) ? 'sometimes|' : '';
        return [
            'name' => $once . 'required',
            'code' => $once . 'required',
            //            'description' =>  $once . 'required',
            'is_active' => $once . 'required',
        ];
    }

    /**
     *
     * Set Error Message
     */
    public static function messages(): array
    {
        return [
            'required' => __('validation.required'),
        ];
    }

    /**
     * Check Validation
     * @param $input
     * @param null $id
     * @return
     */
    public static function validation($input, $id = null): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($input, self::rules($input, $id), self::messages());
    }

    /**
     * default sorting on created at as ascending
     * @param $query
     * @return
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
