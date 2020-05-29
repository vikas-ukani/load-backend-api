<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageConversation extends Model
{

    protected $table  = "message_conversation";

    public $fillable = [
        "from_id", // Message from_id (user_id)
        "to_id", // Message to_id (user_id)
        "last_message", // message body here
        "training_log_id", // training log share id
        "event_id", // event share id
        "unread_count", // Message
    ];

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

    public function from_user()
    {
        return $this->hasOne(User::class, 'id', 'from_id');
    }

    public function to_user()
    {
        return $this->hasOne(User::class, 'id', 'to_id');
    }

}
