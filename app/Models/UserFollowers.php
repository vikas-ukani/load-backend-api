<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFollowers extends Model
{
    protected $table = "user_followers";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', // user id
        'followers_ids', // user followers ids
        'following_ids', // user following ids
    ];

    /**
     * setFollowersIdsAttribute => set array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setFollowersIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['followers_ids'] = $value;
    }

    /**
     * getFollowersIdsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getFollowersIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['followers_ids'] = (isset($value) && count($value)) ?  $value : null;
    }

    /**
     * setFollowingIdsAttribute => set array to string
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function setFollowingIdsAttribute($value)
    {
        if (isset($value) && is_array($value)) {
            $value = array_filter($value);
            $value = implode(',', $value);
        } else {
            $value = null;
        }
        $this->attributes['following_ids'] = $value;
    }

    /**
     * getFollowingIdsAttribute => convert string to array back
     *
     * @param  mixed $value
     *
     * @return void
     */
    public function getFollowingIdsAttribute($value)
    {
        if (isset($value) && is_string($value)) {
            $value = explode(',', $value);
            $value = array_filter($value);
        } else {
            $value = null;
        }
        return $this->attributes['following_ids'] = (isset($value) && count($value)) ?  $value : null;
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
}
