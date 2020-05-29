<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class LoadCenterFeedUsersReport extends Model
{

    protected $table = 'load_center_feed_users_reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'feed_id',
        'feed_report_id',
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
        $once = isset($id) ? 'sometimes|' : '';

        return [
            'user_id' => $once . 'required',
            'feed_id' =>  $once . 'required',
            'feed_report_id' => $once . 'required',
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
            // 'required' => __('validation.required'),
        ];
    }


    protected static function boot()
    {
        parent::boot();

        // self::retrieved(function ($model) {
        //     $model->is_active = $model->is_active === 1;
        // });
    }

    /**
     * scopeOrdered =>default sorting on created at as ascending
     *
     * @param mixed $query
     *
     * @return void
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
