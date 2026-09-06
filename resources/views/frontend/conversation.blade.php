<!--
|--------------------------------------------------------------------------
| resources/views/frontend/conversation.blade.php
|--------------------------------------------------------------------------
-->
@extends('layouts.frontend')

@section('content')
<div class="container-fluid places">

    @php
        $otherParty = Auth::id() == $conversation->guest_id
            ? $conversation->object->user
            : $conversation->guest;
    @endphp

    <h1 class="text-center">
        {{ $conversation->object->name }} &mdash; {{ optional($otherParty)->name }}
    </h1>

    <div class="conversation-thread">
        @foreach ($conversation->messages as $message)
            <div class="media">
                <div class="media-body">
                    <strong>{{ $message->sender->name }}</strong>
                    <small>{{ $message->created_at }}</small>
                    <p>{{ $message->content }}</p>
                </div>
            </div>
            <hr>
        @endforeach
    </div>

    <form method="POST" action="{{ route('postMessage', ['conversation_id' => $conversation->id]) }}">
        @csrf
        <div class="form-group">
            <textarea name="content" class="form-control" placeholder="Write a message" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send</button>
    </form>

</div>
@endsection
