<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommonLibrary extends Model
{
    protected $table = "common_libraries";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'exercise_name', // name of exercise
        'region_id', // from region table
        'body_part_id', // From Body part table
        'body_sub_part_id', // From Body part table
        'mechanics_id', // From mechanics table
        'targeted_muscles_ids', // From targeted_muscles table
        'regions_ids', // From set primary image
        'regions_secondary_ids', // From set secondary image
        'action_force_id', // From action_force table
        'equipment_ids', // From equipment table
        'is_active', //  active or not
    ];

    protected $appends = ['repetition_max', 'is_show_again_message'];

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
            'exercise_name' => $once . 'required|max:200',
            'region_id' => $once . 'required|integer',
            'body_part_id' => $once . 'required|integer',
            'body_sub_part_id' => $once . 'required|integer',
            // 'repetition_max' => $once . 'required|array',
            // 'mechanics_id' => $once.'required|integer',
            // 'targeted_muscles_ids' => $once.'required|integer',
            // 'targeted_muscles_id' => $once.'required|integer',
            // 'action_force_id' => $once.'required|integer',
            // 'equipment_id' => $once.'required|integer',
            // 'is_favorite' => $once.'required|integer',
            // 'is_active' => $once.'required|integer',
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
     * setRegionsIdsAttribute => convert array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setRegionsIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['regions_ids'] =  $value;
    }
    public function setRegionsSecondaryIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['regions_secondary_ids'] =  $value;
    }

    /**
     * getRegionsIdsAttribute  => convert string to array
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getRegionsIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['regions_ids'] = $value;
    }
    public function getRegionsSecondaryIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['regions_secondary_ids'] = $value;
    }

    /**
     * setTargetedMusclesIdsAttribute => convert array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setTargetedMusclesIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['targeted_muscles_ids'] =  $value;
    }

    /**
     * getTargetedMusclesIdsAttribute  => convert string to array
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getTargetedMusclesIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['targeted_muscles_ids'] = $value;
    }

    /**
     * setNameAttribute => set name to Title case
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setExerciseNameAttribute($value)
    {
        $this->attributes['exercise_name'] = ucwords(strtolower($value));
    }

    /**
     * setEquipmentIdsAttribute => convert array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setEquipmentIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['equipment_ids'] =  $value;
    }

    /**
     * getEquipmentIdsAttribute => convert string  to Array
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getEquipmentIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['equipment_ids'] = $value;
    }

    /** add repetition_max data from User's added libraries  */
    public function getRepetitionMaxAttribute($value)
    {
        $userRepetitionMax = CustomCommonLibrariesDetails::where('common_libraries_id', $this->attributes['id'])
            ->where('user_id', Auth::id())
            ->select('repetition_max')
            ->first();
        if (isset($userRepetitionMax, $userRepetitionMax['repetition_max'])) {
            $repMax  = $userRepetitionMax['repetition_max'];
        }
        // return $this->attributes['repetition_max'] = isset($userRepetitionMax['repetition_max']) ? $userRepetitionMax['repetition_max'] : null;
        return $this->attributes['repetition_max'] = $repMax ?? null;
    }

    public function getIsShowAgainMessageAttribute()
    {
        $isShowAgainMessage = CustomCommonLibrariesDetails::where('common_libraries_id', $this->attributes['id'])
            ->where('user_id', Auth::id())
            ->select('is_show_again_message')
            ->first();
        return $this->attributes['is_show_again_message'] =
            (isset($isShowAgainMessage['is_show_again_message']) && $isShowAgainMessage['is_show_again_message'] == 1)
            ? true
            : false;
    }

    /**
     * setRepetitionMaxAttribute => when store in the database set to sting format
     *
     * @param  mixed $value
     *
     * @return void
     */
    // public function setRepetitionMaxAttribute($value)
    // {
    //     $this->attributes['repetition_max'] = json_encode($value);
    // }

    /**
     * getRepetitionMaxAttribute => convert string to object when get data from database
     *
     * @param  mixed $value
     *
     * @return void
     */
    // public function getRepetitionMaxAttribute($value)
    // {
    //     return $this->attributes['repetition_max'] = json_decode($value, true); // true for get in array form
    // }

    /**
     * scopeOrdered =>default sorting on created at as ascending
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
     * targeted_muscle_details => relation with  targeted_muscle_detail multiple
     *
     * @return void
     */
    public function targeted_muscle_details()
    {
        return $this->hasMany('App\Models\TargetedMuscles', 'id', 'targeted_muscles_ids');
    }

    /**
     * user_detail => relation with user details
     *
     * @return void
     */
    public function user_detail()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    /**
     * region_detail => relation with region detail
     *
     * @return void
     */
    // public function region_detail()
    // {
    //     return $this->hasOne('App\Models\User', 'id', 'region_id');
    // }

    /**
     * body_part_detail => relation with body part detail
     *
     * @return void
     */
    public function body_part_detail()
    {
        return $this->hasOne('App\Models\BodyParts', 'id', 'body_part_id');
    }

    /**
     * body_part_detail => relation with body part detail
     *
     * @return void
     */
    public function body_sub_part_detail()
    {
        return $this->hasOne('App\Models\BodyParts', 'id', 'body_sub_part_id');
    }

    /**
     * mechanic_detail => relation with  mechanic_detail
     *
     * @return void
     */
    public function mechanic_detail()
    {
        return $this->hasOne('App\Models\Mechanics', 'id', 'mechanics_id');
    }

    /**
     * targeted_muscle_detail => relation with  targeted_muscle_detail
     *
     * @return void
     */
    public function targeted_muscle_detail()
    {
        return $this->hasOne('App\Models\TargetedMuscles', 'id', 'targeted_muscles_ids');
    }


    /**
     * action_force_detail => relation with  action_force_detail
     *
     * @return void
     */
    public function action_force_detail()
    {
        return $this->hasOne('App\Models\ActionForce', 'id', 'action_force_id');
    }
}
