<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{


    protected $table  = "messages";

    public $fillable = [
        "id",
        "sender_id",  // Message sender id (user_id)
        "receiver_id",  // Message receiver id (user_id)
        "message",  // message body here
    ];
}
