<?php

namespace Tests\Feature;

use App\City;
use App\Reservation;
use App\Room;
use App\TouristObject;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoom()
    {
        $owner = User::factory()->create();
        $city = City::create(['name' => 'Testville']);

        $object = new TouristObject();
        $object->name = 'Test object';
        $object->user_id = $owner->id;
        $object->city_id = $city->id;
        $object->description = 'A place to stay';
        $object->save();

        $room = new Room();
        $room->room_number = 1;
        $room->room_size = 2;
        $room->price = 100;
        $room->description = 'A room';
        $room->object_id = $object->id;
        $room->save();

        return [$room, $city];
    }

    public function testAuthenticatedUserCanBookAnAvailableRoom()
    {
        [$room, $city] = $this->makeRoom();
        $tourist = User::factory()->create();

        $response = $this->actingAs($tourist)->post(route('makeReservation', ['room_id' => $room->id, 'city_id' => $city->id]), [
            'checkin' => now()->addDays(5)->format('Y-m-d'),
            'checkout' => now()->addDays(8)->format('Y-m-d'),
        ]);

        $reservation = Reservation::where('room_id', $room->id)->first();
        $response->assertRedirect(route('checkout', ['reservation_id' => $reservation->id]));
        $this->assertSame(1, Reservation::where('room_id', $room->id)->count());
    }

    public function testOverlappingDatesAreRejected()
    {
        [$room, $city] = $this->makeRoom();
        $firstTourist = User::factory()->create();
        $secondTourist = User::factory()->create();

        $this->actingAs($firstTourist)->post(route('makeReservation', ['room_id' => $room->id, 'city_id' => $city->id]), [
            'checkin' => now()->addDays(5)->format('Y-m-d'),
            'checkout' => now()->addDays(8)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($secondTourist)->post(route('makeReservation', ['room_id' => $room->id, 'city_id' => $city->id]), [
            'checkin' => now()->addDays(7)->format('Y-m-d'),
            'checkout' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('room', ['id' => $room->id, '#reservation']));
        $this->assertSame(1, Reservation::where('room_id', $room->id)->count());
    }

    public function testCheckoutBeforeCheckinIsRejected()
    {
        [$room, $city] = $this->makeRoom();
        $tourist = User::factory()->create();

        $response = $this->actingAs($tourist)->post(route('makeReservation', ['room_id' => $room->id, 'city_id' => $city->id]), [
            'checkin' => now()->addDays(5)->format('Y-m-d'),
            'checkout' => now()->addDays(3)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('checkout');
        $this->assertSame(0, Reservation::where('room_id', $room->id)->count());
    }
}
