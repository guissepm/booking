<?php
/*
|--------------------------------------------------------------------------
| app/Http/Controllers/FrontendController.php *** Copyright netprogs.pl | avaiable only at Udemy.com | further distribution is prohibited  ***
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enjoythetrip\Interfaces\FrontendRepositoryInterface; 
use App\Enjoythetrip\Gateways\FrontendGateway;
use App\Events\OrderPlacedEvent;
use Illuminate\Support\Facades\Cache;   

class FrontendController extends Controller
{
    public function __construct(FrontendRepositoryInterface $frontendRepository, FrontendGateway $frontendGateway)
    {
        /* L24,60*/
        $this->middleware($this->setMiddleware())->only(['makeReservation','addComment','like','unlike','addReview','startConversation','inbox','showConversation','postMessage']);

        $this->fR = $frontendRepository;
        $this->fG = $frontendGateway; 
    }

   /* L12,60 */
        //dd($objects);  
    public function index()
    {
        $objects = $this->fR->getObjectsForMainPage(); 
        //dd($objects);  /* here we execute created in the repositories by adding the second argument. The resulte of this methode is, the liste of touriste object will be assigne to the variable than we assigne this variable to the frontend view L12 */
        return $this->makeResponse('frontend.index',compact('objects')); 
    }
    
    
    /* This function extract an article with the id and assign to the vue the article variable witch contain a data of specifique article L6,22,61 */
    public function article($id)
    {
        $article = $this->fR->getArticle($id); 
        return $this->makeResponse('frontend.article',compact('article')); 
    }
     
    /* L6,15,16,61 */
    public function object($id) 
    {
        $object = $this->fR->getObject($id); 

        return $this->makeResponse('frontend.object',compact('object')); 
    }
    
    /* L6,23 */
    public function person($id)
    {
        $user = $this->fR->getPerson($id); 
        return view('frontend.person', ['user'=>$user]);
    }
    
    /* L6,20,61 */
    public function room($id)
    {
        $room = $this->fR->getRoom($id);
        return $this->makeResponse('frontend.room',compact('room')); 
    }

    /* L20 */
    public function ajaxGetRoomReservations($id)
    {
        
        $reservations = $this->fR->getReservationsByRoomId($id);
        
        return response()->json([
            'reservations'=>$reservations
        ]);
    }
    
    /* L6,18,61 */
    public function roomsearch(Request $request)
    {
    
        if($city = $this->fG->getSearchResults($request))
        {
            return $this->makeResponse('frontend.roomsearch',compact('city'));
        }
        else 
        {
            if (!$request->ajax())
            return redirect('/')->with('norooms', __('No offers were found matching the criteria'));
        }
    }

    /* In this methode we'll refer to ower gateway paterne. This methode accepte the reqest object 
    we stores among html for fild data sent to the server. this data sent by jquery autocomplete, we must
    search the database and return the result in the json form to let jquery autocomplete  L17 */
    public function searchCities(Request $request)
    {

        $results = $this->fG->searchCities($request);

        return response()->json($results);
    }

    /* L61 */
    public function cities()
    {

        $results = $this->fR->cities();

        return response()->json($results);
    }
    

    /* L24,55*/
    public function like($likeable_id, $type, Request $request)
    {
        $this->fR->like($likeable_id, $type, $request);
        
        Cache::flush(); 

        return redirect()->back();
    }
    
    
    /* L24,55 */
    public function unlike($likeable_id, $type, Request $request)
    {
        $this->fR->unlike($likeable_id, $type, $request);

        Cache::flush();
        
        return redirect()->back();
    }

    /* L25,55 */
    public function addComment($commentable_id, $type, Request $request)
    {
        $this->fG->addComment($commentable_id, $type, $request);

        Cache::flush();
        
        return redirect()->back();
    }

    /* L26 */
    public function makeReservation($room_id, $city_id, Request $request)
    {
        $reservation = $this->fG->makeReservation($room_id, $city_id, $request);

        if (!$reservation)
        {
            if (!$request->ajax())
            {
                $request->session()->flash('reservationMsg', __('There are no vacancies'));
                return redirect()->route('room',['id'=>$room_id,'#reservation']);
            }

            return response()->json(['reservation'=>false]);
        }
        else
        {
            if (!$request->ajax())
            return redirect()->route('checkout', ['reservation_id' => $reservation->id]);

            // Mobile/AJAX clients aren't wired up to the Stripe Checkout
            // redirect flow below, so preserve the previous behaviour for
            // them: the reservation is created unpaid and the host is
            // notified immediately, same as before payment existed.
            event( new OrderPlacedEvent($reservation) ); /* L54 */
            return response()->json(['reservation'=>$reservation]);
        }

    }

    public function addReview($reservation_id, Request $request)
    {
        $review = $this->fG->addReview($reservation_id, $request);

        if (!$review)
        {
            $request->session()->flash('reviewMsg', __('This reservation cannot be reviewed'));
            return redirect()->back();
        }

        $request->session()->flash('reviewMsg', __('Review submitted'));
        return redirect()->back();
    }

    public function startConversation($object_id, Request $request)
    {
        $conversation = $this->fG->startConversation($object_id, $request);

        if (!$conversation)
        {
            $request->session()->flash('messageMsg', __('This message could not be sent'));
            return redirect()->back();
        }

        return redirect()->route('showConversation', ['conversation_id' => $conversation->id]);
    }

    public function inbox(Request $request)
    {
        $conversations = $this->fG->getInbox($request);

        return $this->makeResponse('frontend.inbox', compact('conversations'));
    }

    public function showConversation($conversation_id, Request $request)
    {
        $conversation = $this->fG->getConversation($conversation_id, $request);

        if (!$conversation)
        {
            abort(404);
        }

        return $this->makeResponse('frontend.conversation', compact('conversation'));
    }

    public function postMessage($conversation_id, Request $request)
    {
        $message = $this->fG->postMessage($conversation_id, $request);

        if (!$message)
        {
            abort(404);
        }

        return redirect()->route('showConversation', ['conversation_id' => $conversation_id]);
    }
}

 