<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{

	/* L36 */
	public $guarded = []; 
    public $timestamps = false; 
    
    public function users()
    {
        return $this->belongsToMany('App\User');
    }
}
