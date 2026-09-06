<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{

	public $timestamps = false; 

    /* L14 */
    public function photoable()
    {
        return $this->morphTo();
    }

    /* L40 */
    public function getPathAttribute($value)
    {
        return asset("storage/{$value}");
    }
    
    
    /* L40 */
    public function getStoragepathAttribute()
    {
        return $this->original['path'];
    }

    /* L43 */
    public static function imageRules($request,$type)
    {
        $rules = [];

        foreach ($request->file($type) ?? [] as $i => $file)
        {
            $rules["$type.$i"] = 'image|max:4000';
        }

        return $rules;
    }
}
