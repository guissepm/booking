<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['object_id', 'guest_id'];

    public function object()
    {
        return $this->belongsTo('App\TouristObject', 'object_id');
    }

    public function guest()
    {
        return $this->belongsTo('App\User', 'guest_id');
    }

    public function messages()
    {
        return $this->hasMany('App\Message')->orderBy('created_at');
    }

    public function host()
    {
        return $this->object->user;
    }

    public function hasParticipant($userId)
    {
        return $this->guest_id == $userId || $this->object->user_id == $userId;
    }
}
