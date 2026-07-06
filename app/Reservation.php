<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    /* L26 */
    public $timestamps = false; 
    protected $guarded = ['id']; 
    //protected $fillable = ['name'];

    /* L28 */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /* L30 */
    public function room()
    {
        return $this->belongsTo('App\Room');
    } 
     
}
