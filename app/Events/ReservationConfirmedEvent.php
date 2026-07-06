<?php
/*
|--------------------------------------------------------------------------
| app/Events/ReservationConfirmedEvent.php *** Copyright netprogs.pl | avaiable only at Udemy.com | further distribution is prohibited  ***
|--------------------------------------------------------------------------
*/

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Reservation; /* Lecture 54 */

class ReservationConfirmedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $reservation; /* Lecture 54 */

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( Reservation $reservation /* Lecture 54 */ )
    {
        $this->reservation = $reservation; /* Lecture 54 */
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

