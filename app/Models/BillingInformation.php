<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class BillingInformation extends Model
{

    protected $table = "billing_informations";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', // Equipments name
        'credit_card_id', // Equipments name
        'is_default', // Equipments name
        // 'created_at', // record created date
        // 'updated_at', // record updated date
    ];

    /**
     * validation => Check Validation
     *
     * @param mixed $input
     * @param mixed $id
     *
     * @return void
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
     * @return void
     */
    public static function rules($id)
    {
        $once = isset($id) ? 'sometimes|' : '';

        $rules = [
            'user_id' => $once . 'required',
            'credit_card_id' => $once . 'required',
            'is_default' => 'required',
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
     * getIsDefault => to set default true or false
     *
     * @param mixed $value
     *
     * @return void
     */
    public function getIsDefaultAttribute($value)
    {
        return $this->attributes['is_default'] = ($value === 1) ? true : false;
        // $this->attributes['is_default'] = ($value == 1) ? true : false;
    }

    /**
     * scopeOrdered => default sorting on created at as ascending.
     *
     * @param mixed $query
     *
     * @return void
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * user_detail => user relation
     *
     * @return void
     */
    public function user_detail()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
