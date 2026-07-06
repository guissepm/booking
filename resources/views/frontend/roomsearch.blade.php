<!--
|--------------------------------------------------------------------------
| resources/views/frontend/roomsearch.blade.php *** Copyright netprogs.pl | avaiable only at Udemy.com | further distribution is prohibited  ***
|--------------------------------------------------------------------------
-->
@extends('layouts.frontend') <!-- Lecture 5  -->

@section('content') <!-- Lecture 5  -->
<div class="container-fluid places">

    <h1 class="text-center">Available rooms</h1>

    @foreach( $city->rooms->chunk(4) as $chunked_rooms ) <!-- Lecture 19 -->

        <div class="row">

            @foreach( $chunked_rooms as $room ) <!-- Lecture 19 -->

                <div class="col-md-3 col-sm-6">

                    <div class="thumbnail">
                        <img class="img-responsive img-circle" src="{{ $room->photos->first()->path ?? $placeholder /* Lecture 19 */ }}" alt="...">
                        <div class="caption">
                            <h3>Nr {{ $room->room_number /* Lecture 19 */ }} <small class="orange bolded">{{ $room->price /* Lecture 19 */ }}$</small> </h3>
                            <p>{{ str_limit($room->description,80) /* Lecture 19 */ }}</p>
                            <p><a href="{{ route('room',['id'=>$room->id]/* Lecture 19 */) }}" class="btn btn-primary" role="button">Details</a><a href="{{ route('room',['id'=>$room->id]/* Lecture 19 */) }}#reservation" class="btn btn-success pull-right" role="button">Reservation</a></p>
                        </div>
                    </div>
                </div>

            @endforeach <!-- Lecture 19 -->


        </div>

    @endforeach <!-- Lecture 19 -->

</div>
@endsection <!-- Lecture 5  -->


