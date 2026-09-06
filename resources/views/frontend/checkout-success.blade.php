<!--
|--------------------------------------------------------------------------
| resources/views/frontend/checkout-success.blade.php
|--------------------------------------------------------------------------
-->
@extends('layouts.frontend')

@section('content')
<div class="container-fluid places">

    <h1 class="text-center">Thank you!</h1>

    <p class="text-center">
        Your payment for the reservation from {{ $reservation->day_in }} to {{ $reservation->day_out }}
        was received. The host still needs to confirm your stay - you'll find it in
        <a href="{{ route('adminHome') }}">your bookings</a>.
    </p>

</div>
@endsection
