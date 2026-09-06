<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public function reservation()
    {
        return $this->belongsTo('App\Reservation');
    }

    public function author()
    {
        return $this->belongsTo('App\User', 'author_id');
    }

    public function recipient()
    {
        return $this->belongsTo('App\User', 'recipient_id');
    }
}
