<?php

namespace App\Enjoythetrip\Presenters; 

/* Lecture 23 */
trait ObjectPresenter {
    
    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }
    
    public function getLinkAttribute()
    {
        return route('object',['id'=>$this->id]);
    }
    
    public function getTypeAttribute()
    {
        return $this->name.' object';
    }
    
}

