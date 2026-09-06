<?php

namespace Tests\Feature;

use App\Enjoythetrip\Payments\StripeGateway;
use App\Events\OrderPlacedEvent;
use App\City;
use App\Reservation;
use App\Room;
use App\TouristObject;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeReservation($overrides = [])
    {
        $host = User::factory()->create();
        $host->roles()->attach(\App\Role::firstOrCreate(['name' => 'owner'])->id);
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

        $reservation = Reservation::create(array_merge([
            'user_id' => $guest->id,
            'city_id' => $city->id,
            'room_id' => $room->id,
            'status' => 0,
            'day_in' => now()->addDays(5)->format('Y-m-d'),
            'day_out' => now()->addDays(8)->format('Y-m-d'),
            'amount_cents' => 30000,
        ], $overrides));

        return [$reservation, $host, $guest];
    }

    public function testBookingARoomSnapshotsTheAmountInCents()
    {
        $host = User::factory()->create();
        $guest = User::factory()->create();
        $city = City::create(['name' => 'Testville']);
        $object = new TouristObject();
        $object->name = 'Test object';
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

        $this->actingAs($guest)->post(route('makeReservation', ['room_id' => $room->id, 'city_id' => $city->id]), [
            'checkin' => now()->addDays(5)->format('Y-m-d'),
            'checkout' => now()->addDays(8)->format('Y-m-d'),
        ]);

        $reservation = Reservation::first();
        $this->assertSame(30000, $reservation->amount_cents); // 100/night * 3 nights * 100 cents
    }

    public function testMakingAReservationRedirectsToCheckout()
    {
        $host = User::factory()->create();
        $guest = User::factory()->create();
        $city = City::create(['name' => 'Testville']);
        $object = new TouristObject();
        $object->name = 'Test object';
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

        $response = $this->actingAs($guest)->post(route('makeReservation', ['room_id' => $room->id, 'city_id' => $city->id]), [
            'checkin' => now()->addDays(5)->format('Y-m-d'),
            'checkout' => now()->addDays(8)->format('Y-m-d'),
        ]);

        $reservation = Reservation::first();
        $response->assertRedirect(route('checkout', ['reservation_id' => $reservation->id]));
    }

    public function testCheckoutRedirectsToStripeSession()
    {
        [$reservation, $host, $guest] = $this->makeReservation();

        $fakeSession = (object) ['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.com/pay/cs_test_123'];
        $mock = Mockery::mock(StripeGateway::class);
        $mock->shouldReceive('createCheckoutSession')->once()->andReturn($fakeSession);
        $this->app->instance(StripeGateway::class, $mock);

        $response = $this->actingAs($guest)->get(route('checkout', ['reservation_id' => $reservation->id]));

        $response->assertRedirect('https://checkout.stripe.com/pay/cs_test_123');
        $this->assertSame('cs_test_123', $reservation->fresh()->stripe_checkout_session_id);
    }

    public function testCheckoutRejectsAnotherUsersReservation()
    {
        [$reservation, $host, $guest] = $this->makeReservation();
        $stranger = User::factory()->create();

        $this->app->instance(StripeGateway::class, Mockery::mock(StripeGateway::class));

        $this->actingAs($stranger)->get(route('checkout', ['reservation_id' => $reservation->id]))
            ->assertStatus(404);
    }

    public function testCheckoutRejectsAnAlreadyPaidReservation()
    {
        [$reservation, $host, $guest] = $this->makeReservation(['paid_at' => now()]);

        $this->app->instance(StripeGateway::class, Mockery::mock(StripeGateway::class));

        $this->actingAs($guest)->get(route('checkout', ['reservation_id' => $reservation->id]))
            ->assertStatus(404);
    }

    public function testWebhookMarksReservationPaidAndNotifiesHost()
    {
        Event::fake();
        [$reservation, $host, $guest] = $this->makeReservation();

        $fakeEvent = (object) [
            'type' => 'checkout.session.completed',
            'data' => (object) ['object' => (object) [
                'metadata' => (object) ['reservation_id' => $reservation->id],
                'payment_intent' => 'pi_test_123',
            ]],
        ];
        $mock = Mockery::mock(StripeGateway::class);
        $mock->shouldReceive('constructWebhookEvent')->once()->andReturn($fakeEvent);
        $this->app->instance(StripeGateway::class, $mock);

        $response = $this->postJson(route('stripeWebhook'), [], ['Stripe-Signature' => 'sig']);

        $response->assertStatus(200);
        $reservation->refresh();
        $this->assertNotNull($reservation->paid_at);
        $this->assertSame('pi_test_123', $reservation->stripe_payment_intent_id);
        Event::assertDispatched(OrderPlacedEvent::class);
    }

    public function testWebhookDeletesReservationOnExpiredSession()
    {
        [$reservation, $host, $guest] = $this->makeReservation();

        $fakeEvent = (object) [
            'type' => 'checkout.session.expired',
            'data' => (object) ['object' => (object) [
                'metadata' => (object) ['reservation_id' => $reservation->id],
            ]],
        ];
        $mock = Mockery::mock(StripeGateway::class);
        $mock->shouldReceive('constructWebhookEvent')->once()->andReturn($fakeEvent);
        $this->app->instance(StripeGateway::class, $mock);

        $this->postJson(route('stripeWebhook'), [], ['Stripe-Signature' => 'sig'])->assertStatus(200);

        $this->assertNull(Reservation::find($reservation->id));
    }

    public function testWebhookRejectsInvalidSignature()
    {
        $mock = Mockery::mock(StripeGateway::class);
        $mock->shouldReceive('constructWebhookEvent')->once()->andThrow(new \Exception('bad signature'));
        $this->app->instance(StripeGateway::class, $mock);

        $this->postJson(route('stripeWebhook'), [], ['Stripe-Signature' => 'bad'])->assertStatus(400);
    }

    public function testDecliningAPaidReservationIssuesARefund()
    {
        [$reservation, $host, $guest] = $this->makeReservation([
            'paid_at' => now(),
            'stripe_payment_intent_id' => 'pi_test_456',
        ]);

        $mock = Mockery::mock(StripeGateway::class);
        $mock->shouldReceive('refund')->once()->with('pi_test_456');
        $this->app->instance(StripeGateway::class, $mock);

        $this->actingAs($host)->get(route('deleteReservation', ['id' => $reservation->id]));

        $this->assertNull(Reservation::find($reservation->id));
    }

    public function testDecliningAnUnpaidReservationDoesNotIssueARefund()
    {
        [$reservation, $host, $guest] = $this->makeReservation();

        $mock = Mockery::mock(StripeGateway::class);
        $mock->shouldNotReceive('refund');
        $this->app->instance(StripeGateway::class, $mock);

        $this->actingAs($host)->get(route('deleteReservation', ['id' => $reservation->id]));

        $this->assertNull(Reservation::find($reservation->id));
    }
}
