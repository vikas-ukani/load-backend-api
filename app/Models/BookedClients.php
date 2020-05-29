<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class BookedClients extends Model
{
    protected $table = "booked_clients";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'from_id', // Request Sender-Id (user_id).
        'to_id', //  Request Receiver-Id (user_id).
        'selected_date', // Request selected date
        'available_time_id', // From available table id to show name
        'notes', // Set any notes for professional users
        'confirmed_status', // 0 => pending, 1 => accepted, 2 => rejected.
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
        $once = isset($id) ? 'sometimes|' : '';
        $rules = [
            'from_id' =>  $once . 'required',
            'to_id' =>  $once . 'required',
            'selected_date' =>  $once . 'required',
            'available_time_id' =>  $once . 'required',
            'notes' =>  $once . 'required',
            'confirmed_status' =>  $once . 'required',
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
     * @return \Illuminate\Contracts\Validation\Validator
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

    /**
     * from_user_detail => get from user details
     *
     * @return void
     */
    public function from_user_detail()
    {
        return $this->hasOne(User::class, 'id', 'from_id');
    }

    /**
     * to_user_detail => get to user details
     *
     * @return void
     */
    public function to_user_detail()
    {
        return $this->hasOne(User::class, 'id', 'to_id');
    }

    /**
     * available_time => relation with available times
     *
     * @return void
     */
    public function available_time_detail()
    {
        return $this->hasOne(AvailableTimes::class, 'id', 'available_time_id');
    }
}
