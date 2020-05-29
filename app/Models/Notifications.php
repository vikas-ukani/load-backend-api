<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Notifications extends Model
{
    protected $table = "notifications";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', // notification title
        'message', // full notification message
        'read_at', // when use read this notification // set date time
        'body',  //  message body in json formate
        'user_id', // which user to send this notification 
        'created_at', // when notification create 
        'updated_at', // when notification update

    ];

    /** return UTC date */
    protected $dates = [
        'read_at',
    ];

    /**
     * setTitleAttribute => set title to title case
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = ucwords(strtolower($value));
    }

    /**
     * set Validation Rules
     */
    public static function rules($id)
    {
        $rules = [
            'title' => 'required|max:100',
            'message' => 'required|max:200',
            'user_id' => 'required',
        ];
        return $rules;
    }

    /**
     * messages => set Error message
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
     * Check Validation
     */
    public static function validation($input, $id = null)
    {
        return Validator::make($input, Notifications::rules($id), Notifications::messages());
    }

    /**
     * setBodyAttribute => when store in the database set to sting format
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setBodyAttribute($value)
    {
        $this->attributes['body'] = json_encode($value);
    }


    /**
     * getBodyAttribute => convert string to object when get data from database
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getBodyAttribute($value)
    {
        return $this->attributes['body'] = json_decode($value, true); // true for get in array form
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
     * user_detail => User Relation
     *
     * @return void
     */
    public function user_detail()
    {
        return $this->hasOne("App\Models\User", 'id', 'user_id');
    }
}
