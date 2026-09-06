<?php

namespace Tests\Feature;

use App\City;
use App\Conversation;
use App\Message;
use App\Room;
use App\TouristObject;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private function makeObject()
    {
        $host = User::factory()->create();
        $city = City::create(['name' => 'Testville']);

        $object = new TouristObject();
        $object->name = 'Test object';
        $object->user_id = $host->id;
        $object->city_id = $city->id;
        $object->description = 'A place to stay';
        $object->save();

        return [$object, $host];
    }

    public function testGuestCanStartConversationWithHost()
    {
        [$object, $host] = $this->makeObject();
        $guest = User::factory()->create();

        $response = $this->actingAs($guest)->post(route('startConversation', ['object_id' => $object->id]), [
            'content' => 'Is this available in June?',
        ]);

        $conversation = Conversation::first();
        $response->assertRedirect(route('showConversation', ['conversation_id' => $conversation->id]));
        $this->assertSame($object->id, $conversation->object_id);
        $this->assertSame($guest->id, $conversation->guest_id);
        $this->assertSame(1, Message::count());
    }

    public function testHostCannotMessageThemselvesAboutTheirOwnObject()
    {
        [$object, $host] = $this->makeObject();

        $this->actingAs($host)->post(route('startConversation', ['object_id' => $object->id]), [
            'content' => 'Talking to myself',
        ]);

        $this->assertSame(0, Conversation::count());
    }

    public function testSecondMessageReusesTheSameConversation()
    {
        [$object, $host] = $this->makeObject();
        $guest = User::factory()->create();

        $this->actingAs($guest)->post(route('startConversation', ['object_id' => $object->id]), [
            'content' => 'First question',
        ]);
        $this->actingAs($guest)->post(route('startConversation', ['object_id' => $object->id]), [
            'content' => 'Second question',
        ]);

        $this->assertSame(1, Conversation::count());
        $this->assertSame(2, Message::count());
    }

    public function testHostCanReplyInTheConversation()
    {
        [$object, $host] = $this->makeObject();
        $guest = User::factory()->create();

        $this->actingAs($guest)->post(route('startConversation', ['object_id' => $object->id]), [
            'content' => 'Is this available?',
        ]);
        $conversation = Conversation::first();

        $response = $this->actingAs($host)->post(route('postMessage', ['conversation_id' => $conversation->id]), [
            'content' => 'Yes, it is!',
        ]);

        $response->assertRedirect(route('showConversation', ['conversation_id' => $conversation->id]));
        $this->assertSame(2, Message::count());
    }

    public function testUnrelatedUserCannotViewOrReplyToConversation()
    {
        [$object, $host] = $this->makeObject();
        $guest = User::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($guest)->post(route('startConversation', ['object_id' => $object->id]), [
            'content' => 'Is this available?',
        ]);
        $conversation = Conversation::first();

        $this->actingAs($stranger)->get(route('showConversation', ['conversation_id' => $conversation->id]))
            ->assertStatus(404);

        $this->actingAs($stranger)->post(route('postMessage', ['conversation_id' => $conversation->id]), [
            'content' => 'Butting in',
        ])->assertStatus(404);

        $this->assertSame(1, Message::count());
    }

    public function testOpeningConversationMarksOtherPartysMessagesRead()
    {
        [$object, $host] = $this->makeObject();
        $guest = User::factory()->create();

        $this->actingAs($guest)->post(route('startConversation', ['object_id' => $object->id]), [
            'content' => 'Is this available?',
        ]);
        $conversation = Conversation::first();

        $this->actingAs($host)->get(route('showConversation', ['conversation_id' => $conversation->id]));

        $this->assertNotNull(Message::first()->fresh()->read_at);
    }

    public function testInboxListsConversationsForBothGuestAndHost()
    {
        [$object, $host] = $this->makeObject();
        $guest = User::factory()->create();

        $this->actingAs($guest)->post(route('startConversation', ['object_id' => $object->id]), [
            'content' => 'Is this available?',
        ]);

        $this->actingAs($guest)->get(route('inbox'))->assertStatus(200);
        $this->actingAs($host)->get(route('inbox'))->assertStatus(200);
    }
}
