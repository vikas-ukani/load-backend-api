<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class SettingPremium extends Model
{
    protected $table = "setting_premium";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', // who created it
        'about', // about me
        'specialization_ids', // specialization_ids max 3
        'language_ids', // language_ids
        'is_auto_topup', // is auto topup
        'auto_topup_amount', // auto topup amount
    ];

    /**
     * rules => set validation rules
     *
     * @param  mixed $id
     *
     * @return void
     */
    public static function rules($id)
    {
        $rules = [
            // 'name' => 'required|max:200',
            // 'code' => 'required|unique:setting_race_distances,code,' . $id,
            // 'is_active' => 'required',
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
     * scopeOrdered => default sorting on created at as ascending.
     *
     * @param  mixed $query
     *
     * @return void
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function setSpecializationIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['specialization_ids'] =  $value;
    }

    /**
     * getLanguageIdsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getSpecializationIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['specialization_ids'] = $value;
    }

    /**
     * setLanguageIdsAttribute => set array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setLanguageIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['language_ids'] =  $value;
    }

    /**
     * getLanguageIdsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getLanguageIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['language_ids'] = $value;
    }

    /**
     * getIsAutoTopup
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getIsAutoTopupAttribute($value)
    {
        return $this->attributes['is_auto_topup'] = $value == 1 ? true : false;
    }

    /**
     * user_detail => get user detail
     *
     * @return void
     */
    public function user_detail()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function card_details()
    {
        return $this->hasMany(BillingInformation::class, 'user_id', 'user_id');
    }
}
