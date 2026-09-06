<?php

namespace App\Enjoythetrip\Repositories; 

use App\Enjoythetrip\Interfaces\FrontendRepositoryInterface;
use App\{TouristObject,City,Room,Reservation,Article,User,Comment,Review,Conversation,Message,};
use Illuminate\Support\Facades\DB;

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
        return  TouristObject::with(['city','photos', 'address','users.photos','rooms.photos','comments.user','articles.user','rooms.object.city','rooms.reservations.reviews.author'])->find($id);
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
        $dayin = date('Y-m-d', strtotime($request->input('checkin')));
        $dayout = date('Y-m-d', strtotime($request->input('checkout')));

        return DB::transaction(function () use ($room_id, $city_id, $request, $dayin, $dayout) {
            // Lock the room row so concurrent booking attempts for it serialize:
            // whoever gets the lock first checks for overlaps and inserts before
            // the next attempt is allowed to read the (now up to date) reservations.
            $room = Room::where('id', $room_id)->lockForUpdate()->first();

            if (!$room || $this->roomHasOverlap($room_id, $dayin, $dayout)) {
                return null;
            }

            return Reservation::create([
                'user_id'=>$request->user()->id,
                'city_id'=>$city_id,
                'room_id'=>$room_id,
                'status'=>0,
                'day_in'=>$dayin,
                'day_out'=>$dayout,
            ]);
        });
    }

    /* Overlap check done in SQL, inside the locked transaction above, so it
       sees any reservation committed by a concurrent request for this room. */
    private function roomHasOverlap($room_id, $dayin, $dayout)
    {
        return Reservation::where('room_id', $room_id)
            ->where('day_in', '<=', $dayout)
            ->where('day_out', '>=', $dayin)
            ->exists();
    }

    /* Either party to a completed, confirmed reservation (the guest or the
       host) can leave one review about the other. Returns null - instead of
       throwing - on any rule violation (not a party to it, stay not over
       yet, already reviewed), so the controller can show a plain message
       the same way it does for an unavailable room. */
    public function addReview($reservation_id, $request)
    {
        return DB::transaction(function () use ($reservation_id, $request) {
            $reservation = Reservation::with('room.object')
                ->where('id', $reservation_id)
                ->lockForUpdate()
                ->first();

            if (!$reservation) {
                return null;
            }

            $authorId = $request->user()->id;
            $hostId = $reservation->room->object->user_id;

            if ($authorId == $reservation->user_id) {
                $recipientId = $hostId;
            } elseif ($authorId == $hostId) {
                $recipientId = $reservation->user_id;
            } else {
                return null;
            }

            $stayIsOver = $reservation->status == 1
                && $reservation->day_out < date('Y-m-d');

            if (!$stayIsOver) {
                return null;
            }

            $alreadyReviewed = Review::where('reservation_id', $reservation_id)
                ->where('author_id', $authorId)
                ->exists();

            if ($alreadyReviewed) {
                return null;
            }

            $review = new Review;
            $review->reservation_id = $reservation_id;
            $review->author_id = $authorId;
            $review->recipient_id = $recipientId;
            $review->rating = $request->input('rating');
            $review->content = $request->input('content');
            $review->save();

            return $review;
        });
    }

    /* Finds or creates the (object, guest) thread and posts the first
       message into it. Returns null if the object doesn't exist or the
       caller is the object's own owner (messaging yourself makes no
       sense here). */
    public function startConversation($object_id, $request)
    {
        $object = TouristObject::find($object_id);
        $guestId = $request->user()->id;

        if (!$object || $object->user_id == $guestId) {
            return null;
        }

        $conversation = Conversation::firstOrCreate([
            'object_id' => $object_id,
            'guest_id' => $guestId,
        ]);

        $conversation->messages()->create([
            'sender_id' => $guestId,
            'content' => $request->input('content'),
        ]);

        return $conversation;
    }

    /* Every conversation the user takes part in, either as the guest who
       started it or as the host of the object it's about. */
    public function getInbox($request)
    {
        $userId = $request->user()->id;

        return Conversation::with(['object', 'guest', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->where('guest_id', $userId)
            ->orWhereHas('object', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->orderByDesc('updated_at')
            ->get();
    }

    /* Returns null if the conversation doesn't exist or the requesting
       user isn't one of its two participants. Marks the other party's
       messages as read as a side effect of opening the thread. */
    public function getConversation($conversation_id, $request)
    {
        $conversation = Conversation::with(['object', 'guest', 'messages.sender'])
            ->find($conversation_id);

        $userId = $request->user()->id;

        if (!$conversation || !$conversation->hasParticipant($userId)) {
            return null;
        }

        $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $conversation;
    }

    public function postMessage($conversation_id, $request)
    {
        $conversation = Conversation::find($conversation_id);
        $userId = $request->user()->id;

        if (!$conversation || !$conversation->hasParticipant($userId)) {
            return null;
        }

        return $conversation->messages()->create([
            'sender_id' => $userId,
            'content' => $request->input('content'),
        ]);
    }
}


