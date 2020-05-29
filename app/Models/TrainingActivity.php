<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class TrainingActivity extends Model
{

    protected $table = "training_activity";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', /**  name */
        'code', /**  code unique */
        'icon_path', /** activity icon image path */
        'icon_path_red', /** activity icon red image path */
        'sequence', /** sequence wise list */
        'is_active', /** Account is active or deactivate */
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords(strtolower($value));
    }

    /** append base url to event_image with unique
     * @param $value
     * @return string
     */
    protected function getEventImageAttribute($value)
    {
        $this->attributes['icon_path'] = env('APP_URL',  url('/')) . $value;
        $arr = array_unique(explode(env('APP_URL',  url('/')), $this->attributes['icon_path']));
        return $this->attributes['icon_path'] = implode(env('APP_URL',  url('/')), $arr);
    }

    /**
     * @param $id
     * @return array
     */
    public static function rules($id)
    {
        $once = isset($id) ? 'sometimes|' : '';
        return [
            'name' =>  $once . 'required|max:100',
            'code' =>  $once . 'required|unique:training_activity,code,' . $id,
            'icon_path' =>  $once . 'required',
            'is_active' =>  $once . 'required',
        ];
    }

    /** set error message here
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
     * @param $input
     * @param null $id
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, TrainingActivity::rules($id), TrainingActivity::messages());
    }

    /**
     * getIsActiveAttribute
     *
     * @param  mixed $value
     *
     * @return boolean
     */
    public function setIsActiveAttribute($value)
    {
        return $this->attributes['is_active'] =  $value == true  ? 1 : 0;
    }

    /**
     * getIsActiveAttribute
     *
     * @param  mixed $value
     *
     * @return boolean
     */
    public function getIsActiveAttribute($value)
    {
        return $this->attributes['is_active'] =  $value === 1  ? true : false;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * History Relation
     */
    // public function history_details()
    // {
    //     return $this->hasMany("App\Models\History", 'user_id', 'id');
    // }

}
