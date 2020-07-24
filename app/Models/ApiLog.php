<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $table = "api_logs";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', // user's api
        'api_url', // api url
        'method', // api method
        'request', // api request
        'response', // api response
    ];


    // request
    public function setRequestAttribute($value)
    {
        $this->attributes['request'] = json_encode($value);
    }
    public function getRequestAttribute($value)
    {
        return json_decode($value, true); // true for get in array form
    }

    // response
    public function setResponseAttribute($value)
    {
        $this->attributes['response'] = json_encode($value);
    }
    public function getResponseAttribute($value)
    {
        return json_decode($value, true); // true for get in array form
    }
}
