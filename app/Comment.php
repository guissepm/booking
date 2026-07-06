<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use Enjoythetrip\Presenters\CommentPresenter; /* L16 */

    public $timestamps = false; /* L25 */
    
    /* This function will return a commentable oblect L16 */
    public function commentable()
    {
        return $this->morphTo();
    }
    
    /* Touriste object can be commented, so this comment belongsTO some user L16 */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
