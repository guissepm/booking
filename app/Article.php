<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth; /* Lecture 24 */

/* A touriste object may article about itself */
class Article extends Model
{

    use Enjoythetrip\Presenters\ArticlePresenter; 
    /* L45 */
    protected $guarded = []; 
    public $timestamps = false; 
    
    /* Either article was return by user, it's why to use one to many relation ship L16 */
    public function user()
    {
        return $this->belongsTo('App\User');
    } 

    /* The article can be like by many users, this many to many polymorphique relationship. So this article has many users who like this article L22 */
    public function users()
    {
        return $this->morphToMany('App\User', 'likeable');
    }
    
    /* An article has many comments, this one to many polymorphique relationshhip because there will only one table with and comment ca also be about the touriste object L22 */
    public function comments()
    {
        return $this->morphMany('App\Comment', 'commentable');
    }
    
    /* The article belongs to the touriste object  L22 */
    public function object()
    {
        return $this->belongsTo('App\TouristObject','object_id');
    }

    /* Lecture 24 */
    public function isLiked()
    {
        return $this->users()->where('user_id', Auth::user()->id)->exists();
    }
}
