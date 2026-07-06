<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    /* Either have many images, it's a polymorphic relation  L16.
    to imforme laravel that's it's a polymorphic relation we write morphMany inside of hasMany */
    
    public $timestamps = false; 

    public function photos()
    {
        return $this->morphMany('App\Photo', 'photoable');
    }

    /* Eacher belongs to tourist object L17 */
    public function object()
    {
        return $this->belongsTo('App\TouristObject','object_id');
    }
    
    /* L19 */
    public function reservations()
    {
        return $this->hasMany('App\Reservation');
    }
}
