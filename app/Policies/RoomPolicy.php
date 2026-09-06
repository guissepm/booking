<?php

namespace App\Policies;

use App\{User,Room};
use Illuminate\Auth\Access\HandlesAuthorization;

/* L47 */
class RoomPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function checkOwner(User $user, Room $room)
    {
        return $user->id === $room->object->user_id;
    }
}
