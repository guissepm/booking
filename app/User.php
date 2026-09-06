<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/* L27,59 */
class User extends Authenticatable implements \Tymon\JWTAuth\Contracts\JWTSubject
{
    use HasFactory;
    use Notifiable;
    use Enjoythetrip\Presenters\UserPresenter;

    protected $roleCache = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */ 

    /* Hear we add surname column in fillable array.
     *The fillable array speciafy columns that are writable in the database during the users query.
     *This how protect awer appliction frome massasignement exeption. L7 
     */
    protected $fillable = [
        'name', 'email', 'password', 'surname'   
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

     /* L59 */
    public function getJWTIdentifier()
    {
        return $this->getKey(); 
    }

    /* L59 */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /* The inverse of morMany relation from the touriste oblect model is morphedByMany. So this user has many objects that he likes L16 */
    public function objects()
    {
        return $this->morphedByMany('App\TouristObject', 'likeable');
    }
    

    /* This is a riveste many to many polymmorphique relationship. This user has many articles that he likes. L22 */
    public function larticles()
    {
        return $this->morphedByMany('App\Article', 'likeable');
    }
    
    /* The user has many images because all models use one table with images so it's not be a polymorphique relation L16 */
    public function photos()
    {
        return $this->morphMany('App\Photo', 'photoable');
    }

    /* The user has many comments he road L23 */
    public function comments()
    {
        return $this->hasMany('App\Comment');
    }

    /* L49 */
    public function unotifications()
    {
        return $this->hasMany('App\Notification');
    }
    
    /* L27 */
    public function roles()
    {
        return $this->belongsToMany('App\Role');
    }

    /* Reviews other users (guests or hosts) left about this user's stays */
    public function reviewsReceived()
    {
        return $this->hasMany('App\Review', 'recipient_id');
    }

    /* Reviews this user wrote about a stay */
    public function reviewsWritten()
    {
        return $this->hasMany('App\Review', 'author_id');
    }
    
    
    /* L27 */
    public function hasRole(array $roles)
    {

        foreach($roles as $role)
        {

            if(isset($this->roleCache[$role]))
            {
                if($this->roleCache[$role])  return true;

            }
            else
            {
                $this->roleCache[$role] = $this->roles()->where('name', $role)->exists();
                if($this->roleCache[$role]) return true;
            }

        }


        return false;

    }
}
