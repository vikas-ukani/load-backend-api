<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class TrainingGoal extends Model
{

    protected $table = "training_goal";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', // name
        'code', //code unique
        'is_active', // Account is active or deactivate
        'target_hr', // targeted hr
        'training_activity_ids', // training activity ids []
        'training_intensity_ids', // training intensity ids []
        'display_at', // status wise show in list
        'sequence', // sequence wise listing.
        'created_at',
        'updated_at',
    ];

    /**
     * setNameAttribute => set name to title case
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
     * rules => set Validation Rules
     *
     * @param mixed $id
     *
     * @return array
     */
    public static function rules($id)
    {
        return [
            'name' => 'required|max:100',
            'code' => 'required|unique:training_goal,code,' . $id,
            'is_active' => 'required',
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
     * validation => Check Validation
     *
     * @param mixed $input
     * @param mixed $id
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, TrainingGoal::rules($id), TrainingGoal::messages());
    }

    /**
     * getIsActiveAttribute
     *
     * @param mixed $value
     *
     * @return bool
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
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::retrieved(function ($value) {
            if (isset($value) && is_string($value->training_intensity_ids)) {
                $data = explode(',', $value->training_intensity_ids);
                $data = array_filter($data);
            } else {
                $data = null;
            }
            $value->training_intensity_ids = (isset($data) && count($data)) ?  $data : null;

            if (isset($value) && is_string($value->training_activity_ids)) {
                $data = explode(',', $value->training_activity_ids);
                $data = array_filter($data);
            } else {
                $data = null;
            }
            $value->training_activity_ids = (isset($data) && count($data)) ?  $data : null;

            if (isset($value) && is_string($value->display_at)) {
                $data = explode(',', $value->display_at);
                $data = array_filter($data);
            } else {
                $data = null;
            }
            $value->display_at = (isset($data) && count($data)) ?  $data : null;
        });

        // static::creating(function($value) {
        //     //    if (is_string($value))  return explode(',', $value); // // Original
        //     if (isset($value) && is_string($value)) {
        //         $value = explode(',', $value);
        //         $value = array_filter($value);
        //     } else {
        //         $value = null;
        //     }
        //     return $this->attributes['display_at'] = (isset($value) && count($value)) ?  $value : null;

        //     // $value->display_at =
        // });

        // static::addGlobalScope('age', function (Builder $builder) {
        //     $builder->where('age', '>', 200);
        // });
    }
    /**
     * training_intensity_details => get multiple intensity with current goal
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function training_intensity_details()
    {
        return $this->hasMany(TrainingIntensity::class, 'id', 'training_intensity_ids');
    }

    /**
     * History Relation
     */
    // public function history_details()
    // {
    //     return $this->hasMany("App\Models\History", 'user_id', 'id');
    // }

}
