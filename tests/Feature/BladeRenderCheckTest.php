<?php
namespace Tests\Feature;

use App\Address;
use App\City;
use App\Room;
use App\TouristObject;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BladeRenderCheckTest extends TestCase
{
    use RefreshDatabase;

    public function testObjectPageRendersWithReviewsSection()
    {
        $host = User::factory()->create();
        $city = City::create(['name' => 'Testville']);
        $object = new TouristObject();
        $object->name = 'Test object';
        $object->user_id = $host->id;
        $object->city_id = $city->id;
        $object->description = 'desc';
        $object->save();
        $address = new Address();
        $address->number = 1;
        $address->street = 'Test street';
        $address->object_id = $object->id;
        $address->save();
        $room = new Room();
        $room->room_number = 1;
        $room->room_size = 2;
        $room->price = 100;
        $room->description = 'room';
        $room->object_id = $object->id;
        $room->save();

        $response = $this->get(route('object', ['id' => $object->id]));
        $response->assertStatus(200);
    }

    public function testAdminHomeRendersWithPastStaysSection()
    {
        $host = User::factory()->create();
        $guest = User::factory()->create();
        $city = City::create(['name' => 'Testville2']);
        $object = new TouristObject();
        $object->name = 'Test object 2';
        $object->user_id = $host->id;
        $object->city_id = $city->id;
        $object->description = 'desc';
        $object->save();
        $room = new Room();
        $room->room_number = 1;
        $room->room_size = 2;
        $room->price = 100;
        $room->description = 'room';
        $room->object_id = $object->id;
        $room->save();
        \App\Reservation::create([
            'user_id' => $guest->id,
            'city_id' => $city->id,
            'room_id' => $room->id,
            'status' => 1,
            'day_in' => now()->subDays(10)->format('Y-m-d'),
            'day_out' => now()->subDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($host)->get(route('adminHome'));
        $response->assertStatus(200);

        $response2 = $this->actingAs($guest)->get(route('adminHome'));
        $response2->assertStatus(200);
    }
}
