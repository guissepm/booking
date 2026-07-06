<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth; 

/* This a touriste object model L12 */
class TouristObject extends Model
{
    protected $table = 'objects';
    public $timestamps = false; /* Lecture 44 */
    
    use Enjoythetrip\Presenters\ObjectPresenter; /* L23 */
    
    /* L15 */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }
    
    
    /*A touriste object belong to some to some cities. This is a one to many relation ship L14 */
    public function city() 
    {
        return $this->belongsTo('App\City');
    }
    
    /* L35 */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
    
    /* Touriste object have images. This a one to many relationship but polymorphisme L14 */
    public function photos()
    {
        return $this->morphMany('App\Photo', 'photoable');
    }
    
    /* Every touriste object can be like by some users and the user can like many touriste object L16 */
    public function users()
    {
        return $this->morphToMany('App\User', 'likeable');
    }
    
    /* The touriste object have one address and this address belongs to on object L16 */
    public function address()
    {
        return $this->hasOne('App\Address','object_id');
    }
    
    /* The touriste object has many rooms L16 */
    public function rooms()
    {
        return $this->hasMany('App\Room','object_id');
    }
    
    /* Touriste object has many comments, its a on to many polymorphique relationship because many objects vane use comment from only one table 16 */
    public function comments()
    {
        return $this->morphMany('App\Comment', 'commentable');
    }
    
    /* The touridte has many articles and it's not polymorphique one to many relationship because article wil only be about touriste object and nothing more L16 */
    public function articles()
    {
        return $this->hasMany('App\Article','object_id');
    }
    
    /* Lecture 24 */
    public function isLiked()
    {
        return $this->users()->where('user_id', Auth::user()->id)->exists();
    }


}
