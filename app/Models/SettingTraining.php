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
        'hr_rest', // HR max
        'run_auto_pause',
        'cycle_auto_pause',
        'height', // height
        'weight', // weight
        'race_distance_id', // rom race distance table id
        'race_time', // race time

        /** bike settings */
        'bike_weight', // bike_weight
        'bike_wheel_diameter', // bike_wheel_diameter
        'bike_front_chainwheel', // bike_front_chainwheel
        'bike_rear_freewheel', // bike_rear_freewheel
        'physical_activity_level', // bike_rear_freewheel

        /** Time Under tension */
        'training_intensity_id', // bike_rear_freewheel

    ];

    /** for casting tiny integer to boolean values. */
    protected $casts = [
        'run_auto_pause' => 'boolean',
        'cycle_auto_pause' => 'boolean'
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
            // 'hr_rest' => 'required',
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
            $currentYear = (int) \Carbon\Carbon::now()->year;
            $dobArray = explode('-', $user->date_of_birth);
            $birthYear = end($dobArray);
            $age = $currentYear - (int) $birthYear;
            $hrMax = (206.9 - (0.67 * (float) ($age)));
            $hrMax = (string) round($hrMax, 1);
            $value->hr_max = (int) $hrMax;
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
     * setTrainingUnitIdsAttribute
     *
     * @param  mixed $value
     * @return void
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
     * getTrainingUnitIdsAttribute
     *
     * @param  mixed $value
     * @return void
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
