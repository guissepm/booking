<?php

namespace Tests\Feature;

use App\City;
use App\Review;
use App\Room;
use App\TouristObject;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function makePastReservation($overrides = [])
    {
        $host = User::factory()->create();
        $guest = User::factory()->create();
        $city = City::create(['name' => 'Testville']);

        $object = new TouristObject();
        $object->name = 'Test object';
        $object->user_id = $host->id;
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

        $reservation = \App\Reservation::create(array_merge([
            'user_id' => $guest->id,
            'city_id' => $city->id,
            'room_id' => $room->id,
            'status' => 1,
            'day_in' => now()->subDays(10)->format('Y-m-d'),
            'day_out' => now()->subDays(5)->format('Y-m-d'),
        ], $overrides));

        return [$reservation, $host, $guest];
    }

    public function testGuestCanReviewHostAfterCompletedStay()
    {
        [$reservation, $host, $guest] = $this->makePastReservation();

        $response = $this->actingAs($guest)->post(route('addReview', ['reservation_id' => $reservation->id]), [
            'rating' => 5,
            'content' => 'Great host!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'reservation_id' => $reservation->id,
            'author_id' => $guest->id,
            'recipient_id' => $host->id,
            'rating' => 5,
        ]);
    }

    public function testHostCanReviewGuestAfterCompletedStay()
    {
        [$reservation, $host, $guest] = $this->makePastReservation();

        $response = $this->actingAs($host)->post(route('addReview', ['reservation_id' => $reservation->id]), [
            'rating' => 4,
            'content' => 'Great guest!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'reservation_id' => $reservation->id,
            'author_id' => $host->id,
            'recipient_id' => $guest->id,
            'rating' => 4,
        ]);
    }

    public function testCannotReviewBeforeStayIsOver()
    {
        [$reservation, $host, $guest] = $this->makePastReservation([
            'day_in' => now()->addDays(5)->format('Y-m-d'),
            'day_out' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $this->actingAs($guest)->post(route('addReview', ['reservation_id' => $reservation->id]), [
            'rating' => 5,
            'content' => 'Too early!',
        ]);

        $this->assertSame(0, Review::count());
    }

    public function testCannotReviewUnconfirmedStay()
    {
        [$reservation, $host, $guest] = $this->makePastReservation(['status' => 0]);

        $this->actingAs($guest)->post(route('addReview', ['reservation_id' => $reservation->id]), [
            'rating' => 5,
            'content' => 'Never confirmed!',
        ]);

        $this->assertSame(0, Review::count());
    }

    public function testCannotReviewTwice()
    {
        [$reservation, $host, $guest] = $this->makePastReservation();

        $this->actingAs($guest)->post(route('addReview', ['reservation_id' => $reservation->id]), [
            'rating' => 5,
            'content' => 'First review',
        ]);

        $this->actingAs($guest)->post(route('addReview', ['reservation_id' => $reservation->id]), [
            'rating' => 1,
            'content' => 'Second attempt',
        ]);

        $this->assertSame(1, Review::count());
    }

    public function testUnrelatedUserCannotReview()
    {
        [$reservation, $host, $guest] = $this->makePastReservation();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->post(route('addReview', ['reservation_id' => $reservation->id]), [
            'rating' => 5,
            'content' => 'Not my stay',
        ]);

        $this->assertSame(0, Review::count());
    }

    public function testSubmittingAReviewInvalidatesTheCachedObjectPage()
    {
        [$reservation, $host, $guest] = $this->makePastReservation();
        $object = $reservation->room->object;

        // Prime the cache the same way a real visitor would, before the
        // review exists.
        $this->get(route('object', ['id' => $object->id]));

        $this->actingAs($guest)->post(route('addReview', ['reservation_id' => $reservation->id]), [
            'rating' => 5,
            'content' => 'Loved this stay, unmistakably distinctive review text',
        ]);

        $response = $this->get(route('object', ['id' => $object->id]));
        $response->assertSee('Loved this stay, unmistakably distinctive review text');
    }
}
