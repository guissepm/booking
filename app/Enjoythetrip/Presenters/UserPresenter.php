<?php

namespace App\Enjoythetrip\Presenters; /* Lecture 16 */

/* Lecture 16 */
trait UserPresenter {
    
    /*Our presenter will be informe of trait whitch his extension of the class. This trait has been attache to the user model the use instruction inthe class body so all trai methodes will be available in this class   L16 */
    public function getFullNameAttribute()
    {
        return $this->name.' '.$this->surname;
    }
    
}

