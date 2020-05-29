<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Library extends Model
{
    protected $table = "libraries";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'exercise_name', // name of exercise
        'user_id', // who create this library or Null For all users
        'category_id', // from region table
        'regions_ids', // From Body part table
        'mechanics_id', // From mechanics table
        'targeted_muscle', // changed to string
        // 'targeted_muscles_ids', // From targeted_muscles table
        'action_force_id', // From action_force table
        'equipment_ids', // From equipment table
        'repetition_max', // store array object measured RMs weight
        'is_show_again_message', // for show alert message
        'selected_rm', // user selected-rm, 
        'exercise_link', // link exercise url.
        'is_favorite', // to set to favorite
        'is_active', //  active or not
    ];

    // protected $appends = [ 'is_show_again_message'];
    protected $casts = [
        'is_show_again_message' =>  'boolean', 
    ];

    /**
     * rules => set validation rules
     *
     * @param  mixed $id
     *
     * @return array
     */
    public static function rules($id)
    {
        $once = isset($id) ? 'sometimes|' : '';
        return [
            'exercise_name' => $once . 'required|max:200',
            'user_id' => $once . 'required|integer',
            'category_id' => $once . 'required|integer',
            'regions_ids' => $once . 'required|array',
            'repetition_max' => $once . 'required|array',
            'mechanics_id' => $once . 'integer',
            // 'targeted_muscles_ids' => $once.'required|integer',
            // 'targeted_muscles_id' => $once.'required|integer',
            'action_force_id' => $once . 'integer',
            // 'equipment_ids' => $once.'required|integer',
            // 'is_favorite' => $once.'required|integer',
            'is_active' => $once . 'boolean',
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
     * validation => Check Validation
     *
     * @param  mixed $input
     * @param  mixed $id
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, Library::rules($id), Library::messages());
    }

    protected static function boot()
    {
        parent::boot();
        static::retrieved(function ($model) {
            $model->is_show_again_message = (isset($model->is_show_again_message) && $model->is_show_again_message == 1) ? true : false ;
        });
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

    /**
     * getRegionsIdsAttribute  => convert string to array
     *
     * @param  mixed $value
     *
     * @return array
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
     * getEquipmentIdsAttribute  => convert string to array
     *
     * @param  mixed $value
     *
     * @return array
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
     * @return array
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
     * setRepetitionMaxAttribute => when store in the database set to sting format
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setRepetitionMaxAttribute($value)
    {
        $this->attributes['repetition_max'] = json_encode($value);
    }

    /**
     * getRepetitionMaxAttribute => convert string to object when get data from database
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getRepetitionMaxAttribute($value)
    {
        return $this->attributes['repetition_max'] = json_decode($value, true); // true for get in array form
    }

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function targeted_muscle_details()
    {
        return $this->hasMany('App\Models\TargetedMuscles', 'id', 'targeted_muscles_ids');
    }

    /**
     * user_detail => relation with user details
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function body_part_detail()
    {
        return $this->hasOne('App\Models\BodyParts', 'id', 'body_part_id');
    }

    /**
     * body_part_detail => relation with body part detail
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|void
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|void
     */
    public function targeted_muscle_detail()
    {
        return $this->hasOne('App\Models\TargetedMuscles', 'id', 'targeted_muscles_ids');
    }

    /**
     * action_force_detail => relation with  action_force_detail
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function action_force_detail()
    {
        return $this->hasOne('App\Models\ActionForce', 'id', 'action_force_id');
    }
}
