<?php

namespace App\Enjoythetrip\Repositories; 

use App\Enjoythetrip\Interfaces\FrontendRepositoryInterface; 
use App\{TouristObject,City,Room,Reservation,Article,User,Comment,}; 

/* The Frontend repository file has the name implase will respible for communication with the database for the visible part of the application for user that are logued in L12 */
class FrontendRepository  implements FrontendRepositoryInterface  {  
    
    /* Thi methode return all touristes objects using paginate methodes to have pagination in the main page L12, L15 */
    public function getObjectsForMainPage()
    {
        // return TouristObject::all(); 
        return TouristObject::with(['city','photos'])->ordered()->paginate(8); 
    } 
    
    
    /* This methode is use to get a tourist object using the find methode L15 */
    public function getObject($id)
    {
        //return TouristObject::find($id); 
        
        // rooms.object.city   for json mobile because there is no lazy loading there
        return  TouristObject::with(['city','photos', 'address','users.photos','rooms.photos','comments.user','articles.user','rooms.object.city'])->find($id); 
    }
    
    
    /* L17 */
    public function getSearchCities( string $term)
    {
        return  City::where('name', 'LIKE', $term . '%')->get();               
    } 
    
    
    /* L18 */
    public function getSearchResults( string $city)
    {
        // rooms.object.photos  for json mobile
        return  City::with(['rooms.reservations','rooms.photos','rooms.object.photos'])->where('name',$city)->first() ?? false;  /* L19 */
    } 
    
    /* L20 */
    public function getRoom($id)
    {
        // with - for mobile json
        return  Room::with(['object.address'])->find($id);
    } 
    
    
    /* L20 */
    public function getReservationsByRoomId( $room_id )
    {
        return  Reservation::where('room_id',$room_id)->get(); 
    } 

    /* This function is use to get an article by the id. We extract depend reletion the with methode L22 */
    public function getArticle($id)
    {
        return  Article::with(['object.photos','comments'])->find($id);
    } 
    
    /* This function extrct user data via eager a loadding. that is we load dependance modal L23 */
    public function getPerson($id)
    {
        return  User::with(['objects','larticles','comments.commentable'])->find($id);
    }

    /* Types the client is allowed to reference by name in like/unlike/addComment routes. */
    const LIKEABLE_TYPES = ['App\TouristObject', 'App\Article'];

    /* L24 */
    public function like($likeable_id, $type, $request)
    {
        $likeable = $this->resolveLikeable($type)::find($likeable_id);

        return $likeable->users()->attach($request->user()->id);
    }

    /* L24 */
    public function unlike($likeable_id, $type, $request)
    {
        $likeable = $this->resolveLikeable($type)::find($likeable_id);

        return $likeable->users()->detach($request->user()->id);
    }

    private function resolveLikeable($type)
    {
        if (!in_array($type, self::LIKEABLE_TYPES, true))
        {
            abort(404);
        }

        return $type;
    }

    /* L25 */
    public function addComment($commentable_id, $type, $request)
    {
        $commentable = $this->resolveLikeable($type)::find($commentable_id);

        $comment = new Comment;

        $comment->content = $request->input('content');

        $comment->rating = $type == 'App\TouristObject' ? $request->input('rating') : 0;

        $comment->user_id = $request->user()->id;

        return $commentable->comments()->save($comment);
    }

    /* L26 */
    public function makeReservation($room_id, $city_id, $request)
    {
        return Reservation::create([
                'user_id'=>$request->user()->id,
                'city_id'=>$city_id,
                'room_id'=>$room_id,
                'status'=>0,
                'day_in'=>date('Y-m-d', strtotime($request->input('checkin'))),
                'day_out'=>date('Y-m-d', strtotime($request->input('checkout')))
            ]);
    }


  
}


