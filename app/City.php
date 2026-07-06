<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    //protected $table = 'table_name'; 
    /* L38 */
    protected $guarded = [];
    public $timestamps = false; 

    /* L39 */
    public function rooms()
    {
        return $this->hasManyThrough('App\Room', 'App\TouristObject','city_id','object_id');
    }

    /* L61 */
    public function reservations()
    {
        return $this->hasMany('App\Reservation');
    }
}
