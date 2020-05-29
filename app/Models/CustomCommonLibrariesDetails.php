<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CustomCommonLibrariesDetails extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'common_libraries_id', // name of exercise
        'user_id', // who create this library or Null For all users
        'repetition_max', // store array object measured RMs weight
        'selected_rm', // store custom selected rm
        'is_show_again_message', // for show alert message
    ];

    /**
     * validation => Check Validation
     *
     * @param mixed $input
     * @param mixed $id
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validation($input, $id = null): \Illuminate\Contracts\Validation\Validator
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
    public static function rules($id): array
    {
        $once = isset($id) ? 'sometimes|' : '';
        return [
            'common_libraries_id' => $once . 'required',
            'user_id' => $once . 'required',
            'repetition_max' => $once . 'required',
        ];
    }

    /**  Set Error Message
     * @return array
     */
    public static function messages(): array
    {
        return [
            'required' => __('validation.required'),
        ];
    }

    /**
     * Model Binding Concept
     */
//    public static function boot()
//    {
//        parent::boot();

//        static::creating(function ($model) {
////            $model->repetition_max = is_array($model->repetition_max) ? json_encode($model->repetition_max) : null;
//            $model->repetition_max = json_encode($model->repetition_max);
//        });

//        static::updating(function ($model) {
////            $model->repetition_max = is_array($model->repetition_max) ? json_encode($model->repetition_max) : null;
//            $model->repetition_max = json_encode($model->repetition_max);
//        });

//        static::saving(function ($model) {
////            $model->repetition_max = json_encode($model->repetition_max);
//////            $model->repetition_max = is_array($model->repetition_max) ? json_encode($model->repetition_max) : null;;
////        });
//    }

    /** Default sorting on created at as ascending
     * @param $query
     * @return mixed
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * setRepetitionMaxAttribute => when store in the database set to sting format
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setRepetitionMaxAttribute($value)
    {
        $this->attributes['repetition_max'] = json_encode($value);
    }

    /**
     * getRepetitionMaxAttribute => Convert string to object when get data from database
     *
     * @param mixed $value
     *
     * @return void
     */
    public function getRepetitionMaxAttribute($value)
    {
        return $this->attributes['repetition_max'] = is_string($value) ? json_decode($value, true) : $value; // true for get in array form
//          $this->attributes['repetition_max'] = (isset($stringData)) ? json_decode($value, true) : null; // true for get in array form
    }

    public function common_libraries_detail()
    {
        return $this->hasOne(CommonLibrary::class, 'id', 'common_libraries_id');
    }

    public function user_detail()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

}
