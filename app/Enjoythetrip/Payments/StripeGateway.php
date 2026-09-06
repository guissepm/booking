<?php

namespace App\Enjoythetrip\Payments;

use App\Reservation;
use Stripe\StripeClient;
use Stripe\Webhook;

/* Thin wrapper around the Stripe SDK so PaymentController stays testable
   without needing real API credentials for anything except an actual
   checkout run. */
class StripeGateway
{
    private $client;

    /* Lazy: building a StripeClient with no secret key configured throws
       immediately, so this must not run just because something resolved
       this class out of the container - only once a Stripe call is
       actually made. */
    private function client()
    {
        if (!$this->client)
        {
            $this->client = new StripeClient(config('services.stripe.secret'));
        }

        return $this->client;
    }

    public function createCheckoutSession(Reservation $reservation)
    {
        return $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $reservation->amount_cents,
                    'product_data' => [
                        'name' => 'Reservation at '.$reservation->room->object->name,
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'reservation_id' => $reservation->id,
            ],
            // Stripe requires this at least 30 minutes out; matches how
            // long an unpaid reservation is allowed to hold the room dates
            // before checkout.session.expired frees them again.
            'expires_at' => now()->addMinutes(30)->timestamp,
            'success_url' => route('checkoutSuccess', ['reservation_id' => $reservation->id]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkoutCancel', ['reservation_id' => $reservation->id]),
        ]);
    }

    public function constructWebhookEvent($payload, $signature)
    {
        return Webhook::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));
    }

    public function refund($paymentIntentId)
    {
        return $this->client()->refunds->create(['payment_intent' => $paymentIntentId]);
    }
}
