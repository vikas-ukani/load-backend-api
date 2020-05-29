<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class FeedComments extends Model
{
    protected $table = "feed_comments";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'feed_id', // training log id as feed id
        'user_id', // user id 
        'comment', // comment 
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
            'feed_id' => $once . 'required|integer',
            'user_id' => $once . 'required|integer',
            'comment' => $once . 'required'
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
            // 'required' => __('validation.required'),
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
        return Validator::make($input, FeedComments::rules($id), FeedComments::messages());
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

    public function user_detail()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
