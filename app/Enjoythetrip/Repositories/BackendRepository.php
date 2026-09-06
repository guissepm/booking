<?php

namespace App\Enjoythetrip\Repositories; 

use App\Enjoythetrip\Interfaces\BackendRepositoryInterface;
use App\Enjoythetrip\Payments\StripeGateway;
use App\{TouristObject,Reservation,City,User,Photo,Address,Article,Room,Notification};
use Illuminate\Support\Facades\Auth;

/* Lecture 27 */
class BackendRepository implements BackendRepositoryInterface  {

    private $stripe;

    public function __construct(StripeGateway $stripe)
    {
        $this->stripe = $stripe;
    }

    
    
    /* Lecture 28 */
    public function getOwnerReservations($request)
    {
        return TouristObject::with([

                  'rooms' => function($q) {
                        $q->has('reservations'); // works like where clause for Room
                    }, // give me rooms only with reservations, if it wasn't there would be rooms without reservations

                    'rooms.reservations.user',
                    'rooms.reservations.reviews.author'

                  ])
                    ->has('rooms.reservations') // ensures that it gives me only those objects that have at least one reservation, has() here works like where clause for Object
                    ->where('user_id', $request->user()->id)
                    ->get();
    }
    
    
    /* L28 */
    public function getTouristReservations($request)
    {

       return TouristObject::with([

                    'rooms.reservations' => function($q) use($request) { // filters reserervations of other users

                            $q->where('user_id',$request->user()->id);

                    },

                    'rooms'=>function($q) use($request){
                        $q->whereHas('reservations',function($query) use($request){
                            $query->where('user_id',$request->user()->id);
                        });
                    },
                    
                    'rooms.reservations.user',
                    'rooms.reservations.reviews.author'

                  ])

                    ->whereHas('rooms.reservations',function($q) use($request){  // acts like has() with additional conditions

                        $q->where('user_id',$request->user()->id);

                    })
                    ->get();
    }
    
    /* L30 */
    public function getReservationData($request)
    {
        $query = Reservation::with('user', 'room')
                ->where('room_id', $request->input('room_id'))
                ->where('day_in', '<=', date('Y-m-d', strtotime($request->input('date'))))
                ->where('day_out', '>=', date('Y-m-d', strtotime($request->input('date'))));

        if (!$request->user()->hasRole(['admin']))
        {
            $query->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                  ->orWhereHas('room.object', function ($q2) use ($request) {
                      $q2->where('user_id', $request->user()->id);
                  });
            });
        }

        return $query->first();
    }
    
    
    /* L35 */
    public function getReservation($id)
    {
        return Reservation::find($id);
    }
    
    
    /* L35 */
    public function deleteReservation(Reservation $reservation)
    {
        if ($reservation->paid_at)
        {
            $this->stripe->refund($reservation->stripe_payment_intent_id);
        }

        return $reservation->delete();
    }
    
    
    /* L35 */
    /* A reservation can only be confirmed once it's actually been paid for -
       otherwise a host could confirm (and a guest could then stay in) a
       booking nobody ever paid for, whether that's a web reservation whose
       Stripe webhook hasn't landed yet or an AJAX/mobile one that was never
       routed through Checkout at all. */
    public function confirmReservation(Reservation $reservation)
    {
        if (!$reservation->paid_at)
        {
            return false;
        }

        return $reservation->update(['status' => true]);
    }
    
    /* L37 */
    public function getCities()
    {
        return City::orderBy('name','asc')->get();
    }
    
    
    /* L37 */
    public function getCity($id)
    {
        return City::find($id);
    }
    
    
    /* L37 */
    public function createCity($request)
    {
        return City::create([
            'name' => $request->input('name')
        ]);
    }
    
    
    /* L37 */
    public function updateCity($request, $id)
    {
        return City::where('id',$id)->update([
            'name' => $request->input('name')
        ]);
    }
    
    
    /* L37 */
    public function deleteCity($id)
    {
        return City::where('id',$id)->delete();
    }
    
    
    /* L39 */
    public function saveUser($request)
    {
        $user = User::find($request->user()->id);
        $user->name = $request->input('name');
        $user->surname = $request->input('surname');
        $user->email = $request->input('email');
        $user->save();

        return $user;
    }
    
    
    /* L40 */
    public function getPhoto($id)
    {
        return Photo::find($id);
    }
    
    
    /* L40 */
    public function updateUserPhoto(User $user,Photo $photo)
    {
        return $user->photos()->save($photo);
    }
    
    /* L40 */
    public function createUserPhoto($user,$path)
    {
        $photo = new Photo;
        $photo->path = $path;
        $user->photos()->save($photo);
    }
    
    /* L40 */
    public function deletePhoto(Photo $photo)
    {
        $path = $photo->storagepath;
        $photo->delete();
        return $path;
    }
    
    
    /* L42 */
    public function getObject($id)
    {
        return TouristObject::find($id);
    }
    
    
    /* L42 */
    public function updateObjectWithAddress($id, $request)
    {

        Address::where('object_id',$id)->update([
            'street'=>$request->input('street'),
            'number'=>$request->input('number'),
            ]);

        $object = TouristObject::find($id);


        $object->name = $request->input('name');
        $object->city_id = $request->input('city');
        $object->description = $request->input('description');

        $object->save();

        return $object;

    }
    
    
    /* L42 */
    public function createNewObjectWithAddress($request)
    {
        $object = new TouristObject;
        $object->user_id = $request->user()->id;

        $object->name = $request->input('name');
        $object->city_id = $request->input('city');
        $object->description = $request->input('description');

        $object->save();


        $address = new Address;
        $address->street = $request->input('street');
        $address->number = $request->input('number');
        $address->object_id = $object->id;
        $address->save();
        $object->address()->save($address);

        return $object;
    }
    
    
    /* L43 */
    public function saveObjectPhotos(TouristObject $object, string $path)
    {

        $photo = new Photo;
        $photo->path = $path;
        return $object->photos()->save($photo);

    }
    
    
    /* L45 */
    public function saveArticle($object_id,$request)
    {
            return Article::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'user_id' => $request->user()->id,
            'object_id' =>$object_id,
            'created_at' => new \DateTime(),
        ]);
    }
    
    /* Lecture 45 */
    public function getArticle($id)
    {
        return Article::find($id);
    }
    
    
    /* L45 */
    public function deleteArticle(Article $article)
    {
        return  $article->delete();
    }
    
    
    /* L46 */
    public function getMyObjects($request)
    {
        return TouristObject::where('user_id',$request->user()->id)->get();
    }
    
    
    /* L46 */
    public function deleteObject($id)
    {
        return TouristObject::where('id',$id)->delete();
    }
    
    
    /* L47 */
    public function getRoom($id)
    {
        return Room::find($id);
    }
    
    
    /* L48 */
    public function updateRoom($id,$request)
    {
        $room = Room::find($id);
        $room->room_number = $request->input('room_number');
        $room->room_size = $request->input('room_size');
        $room->price = $request->input('price');
        $room->description = $request->input('description');

        $room->save();

        return $room;
    }
    
    
    /* L48 */
    public function createNewRoom($request)
    {
        $room = new Room;
        $object = TouristObject::find( $request->input('object_id') );
        $room->object_id = $request->input('object_id') ;

        $room->room_number = $request->input('room_number');
        $room->room_size = $request->input('room_size');
        $room->price = $request->input('price');
        $room->description = $request->input('description');

        $room->save();

        $object->rooms()->save($room);

        return $room;
    }
    
    
    /* L48 */
    public function saveRoomPhotos(Room $room, string $path)
    {
        $photo = new Photo;
        $photo->path = $path;
        return $room->photos()->save($photo); 
    }
    
    
    /* L48 */
    public function deleteRoom(Room $room)
    {
        return $room->delete();
    }
    
    
    /* L50 */
    public function setReadNotifications($request)
    {
       return Notification::where('id', $request->input('id'))
                        ->where('user_id', $request->user()->id)
                        ->update(['status' => 1]);
    }

    /* Lecture 52 */
    public function getUserNotifications($id)
    {
        return Notification::where('user_id', $id)->where('shown', 0)->get();
    }
    
    
    /* Lecture 52 */
    public function setShownNotifications($request)
    {
        return Notification::whereIn('id', $request->input('idsOfNotShownNotifications'))
                        ->where('user_id', $request->user()->id)
                        ->update(['shown' => 1]);
    }

    /* Lecture 53 */
    public function getNotifications()
    {
        return Notification::where('user_id', Auth::user()->id )->where('status',0)->get(); // for mobile
    }
    
    
}


