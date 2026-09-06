<!--
|--------------------------------------------------------------------------
| resources/views/frontend/inbox.blade.php
|--------------------------------------------------------------------------
-->
@extends('layouts.frontend')

@section('content')
<div class="container-fluid places">

    <h1 class="text-center">Messages</h1>

    @if ($conversations->isEmpty())
        <p class="text-center">You have no conversations yet.</p>
    @endif

    <div class="list-group">
        @foreach ($conversations as $conversation)
            @php
                $otherParty = Auth::id() == $conversation->guest_id
                    ? $conversation->object->user
                    : $conversation->guest;
                $lastMessage = $conversation->messages->first();
            @endphp
            <a href="{{ route('showConversation', ['conversation_id' => $conversation->id]) }}" class="list-group-item">
                <h4 class="list-group-item-heading">{{ $conversation->object->name }} &mdash; {{ optional($otherParty)->name }}</h4>
                @if ($lastMessage)
                    <p class="list-group-item-text">{{ \Illuminate\Support\Str::limit($lastMessage->content, 100) }}</p>
                @endif
            </a>
        @endforeach
    </div>

</div>
@endsection
