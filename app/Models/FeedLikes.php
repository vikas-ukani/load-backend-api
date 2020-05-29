<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedLikes extends Model
{
    protected $table = "feed_likes";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'feed_id', // feed id for likes
        'user_ids', // users id for liked users 
    ];

    /**
     * setUserIdsAttribute => set array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setUserIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['user_ids'] = $value;
    }

    /**
     * getUserIdsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getUserIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return (isset($value) && count($value)) ?  $value : null;
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
     * liked_detail => get likes details
     *
     * @return void
     */
    public function liked_detail()
    {
        // return $this->hasOne(::class, 'id', 'user_id');
    }

    /**
     * user_detail => get users details
     *
     * @return void
     */
    public function user_detail()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
