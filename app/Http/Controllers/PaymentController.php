<?php

namespace App\Http\Controllers;

use App\Enjoythetrip\Payments\StripeGateway;
use App\Events\OrderPlacedEvent;
use App\Reservation;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private $stripe;

    public function __construct(StripeGateway $stripe)
    {
        $this->middleware('auth')->except(['webhook']);
        $this->stripe = $stripe;
    }

    public function checkout($reservation_id, Request $request)
    {
        $reservation = Reservation::with('room.object')->findOrFail($reservation_id);

        if ($reservation->user_id != $request->user()->id || $reservation->paid_at)
        {
            abort(404);
        }

        $session = $this->stripe->createCheckoutSession($reservation);

        $reservation->update(['stripe_checkout_session_id' => $session->id]);

        return redirect($session->url);
    }

    public function success($reservation_id, Request $request)
    {
        $reservation = Reservation::findOrFail($reservation_id);

        if ($reservation->user_id != $request->user()->id)
        {
            abort(404);
        }

        return view('frontend.checkout-success', compact('reservation'));
    }

    public function cancel($reservation_id, Request $request)
    {
        $reservation = Reservation::find($reservation_id);

        if ($reservation && $reservation->user_id == $request->user()->id && !$reservation->paid_at)
        {
            $reservation->delete();
        }

        $request->session()->flash('reservationMsg', __('Checkout cancelled'));
        return redirect()->route('home');
    }

    /* Stripe calls this directly - no session, no CSRF token, so it's
       excluded from both the auth middleware above and CSRF verification
       (see App\Http\Middleware\VerifyCsrfToken). Authenticity comes from
       the signed payload instead. */
    public function webhook(Request $request)
    {
        try
        {
            $event = $this->stripe->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature')
            );
        }
        catch (\Exception $e)
        {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed')
        {
            $session = $event->data->object;
            $reservation = Reservation::find($session->metadata->reservation_id ?? null);

            if ($reservation && !$reservation->paid_at)
            {
                $reservation->update([
                    'paid_at' => now(),
                    'stripe_payment_intent_id' => $session->payment_intent,
                ]);

                event(new OrderPlacedEvent($reservation));
            }
        }

        if ($event->type === 'checkout.session.expired')
        {
            $session = $event->data->object;
            $reservation = Reservation::find($session->metadata->reservation_id ?? null);

            if ($reservation && !$reservation->paid_at)
            {
                $reservation->delete();
            }
        }

        return response('OK', 200);
    }
}
