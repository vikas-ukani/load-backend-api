<?php

namespace App\Models;

use App\Supports\DateConvertor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class SettingTraining extends Model
{
    use DateConvertor;

    protected $table = 'setting_trainings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', // Which user id can store it.
        'training_unit_ids',
        'hr_max', // HR max
        'height', // height
        'weight', // weight
        'race_distance_id', // rom race distance table id
        'race_time', // race time
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
        return [
            // 'user_id' => 'required|unique:setting_trainings,code,' . $id,
            // 'hr_max' => 'required',
            // 'height' => 'required',
            // 'race_distance_id' => 'required',
            // 'race_time' => 'required',
        ];
    }

    /**
     * messages => Set Error Message
     *
     * @return array
     */
    public static function messages()
    {
        /** set error message in trans files */
        return [
            'required' => __('validation.required'),
        ];
    }

    /**
     *
     */
    protected static function boot()
    {
        parent::boot();

        static::retrieved(function ($value) {
            /** calculate hr_max for User Setting to show in  setting module. */
            $user = \Auth::user();
            $currentYear = (int)\Carbon\Carbon::now()->year;
            $dobArray = explode('-', $user->date_of_birth);
            $birthYear = end($dobArray);
            $age = $currentYear - (int)$birthYear;
            $hrMax = (206.9 - (0.67 * (float)($age)));
            $hrMax = (string)round($hrMax, 1);
            $value->hr_max = (int)$hrMax;
        });
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
     * @param $value
     */
    public function setTrainingUnitIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['training_unit_ids'] = $value;
    }

    /**
     * @param $value
     * @return array|null
     */
    public function getTrainingUnitIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['training_unit_ids'] = $value;
    }

    /**
     * race_distance_detail => relation with setting race distance detail
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function race_distance_detail()
    {
        return $this->hasOne(SettingRaceDistance::class, 'id', 'race_distance_id');
    }

    /**
     * user_detail => get user detail who create this setting training
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user_detail()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
