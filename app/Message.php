<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['sender_id', 'content'];

    /* Bumps the parent conversation's updated_at whenever a message is
       saved, so the inbox can sort threads by latest activity. */
    protected $touches = ['conversation'];

    public function conversation()
    {
        return $this->belongsTo('App\Conversation');
    }

    public function sender()
    {
        return $this->belongsTo('App\User', 'sender_id');
    }
}
