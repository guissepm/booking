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

        // Re-opening checkout (back button, a stale link, a double click)
        // must not spawn a second live session for the same reservation:
        // the webhook only has metadata.reservation_id to go on, so two
        // open sessions racing each other could double-charge, or the
        // older one expiring could delete the reservation out from under
        // a still-payable newer one.
        if ($reservation->stripe_checkout_session_id)
        {
            $existing = $this->stripe->retrieveSession($reservation->stripe_checkout_session_id);

            if ($existing->status === 'open')
            {
                return redirect($existing->url);
            }
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
            // Expire the session before deleting the reservation it points
            // to: otherwise a guest who still has the Checkout tab open
            // could pay right after landing here, and the completion
            // webhook would find no reservation left to mark paid - a
            // real charge with no booking behind it.
            if ($reservation->stripe_checkout_session_id)
            {
                $this->stripe->expireSession($reservation->stripe_checkout_session_id);
            }

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

            // Ignore a session that isn't the reservation's current one -
            // it was superseded by a later checkout attempt, so acting on
            // it would risk marking the reservation paid from the wrong
            // session (or reviving one already deleted for a different
            // session's expiry).
            if ($reservation && !$reservation->paid_at && $reservation->stripe_checkout_session_id === $session->id)
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

            if ($reservation && !$reservation->paid_at && $reservation->stripe_checkout_session_id === $session->id)
            {
                $reservation->delete();
            }
        }

        return response('OK', 200);
    }
}
