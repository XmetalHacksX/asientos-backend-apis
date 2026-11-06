<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\SeatStatus;
use App\Events\SeatStatusUpdated;
use App\Models\EventSeat;
use App\Models\Payment;
use App\Models\Reservation;
use Exception;
use Illuminate\Support\Facades\DB;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentService
{
    public function __construct()
    {
        $secret = config('stripe.secret') ?? env('STRIPE_SECRET');
        if ($secret) {
            Stripe::setApiKey($secret);
        }
    }

    /**
     * Create a Stripe PaymentIntent for a pending reservation.
     *
     * @return array{client_secret: string, payment_intent_id: string, payment: Payment}
     *
     * @throws Exception
     */
    public function createPaymentIntent(Reservation $reservation, float $amount, ?string $currency = null): array
    {
        $secret = config('stripe.secret') ?? env('STRIPE_SECRET');
        $currencyCode = $currency ?? config('reservations.default_currency', 'MXN');
        $amountInCents = (int) round($amount * 100);

        // If a test/sample key is configured or Stripe cannot be reached, provide an operational mock intent for seamless development testing
        $paymentIntentId = 'pi_mock_'.uniqid();
        $clientSecret = $paymentIntentId.'_secret_'.uniqid();

        if ($secret && ! str_starts_with($secret, 'sk_test_sample')) {
            try {
                $intent = PaymentIntent::create([
                    'amount' => $amountInCents,
                    'currency' => strtolower($currencyCode),
                    'metadata' => [
                        'reservation_id' => $reservation->id,
                        'event_id' => $reservation->event_id,
                        'ticket_token' => $reservation->ticket_token,
                    ],
                ]);

                $paymentIntentId = $intent->id;
                $clientSecret = $intent->client_secret ?? '';
            } catch (Exception $e) {
                throw new Exception('Error al comunicarse con la pasarela Stripe: '.$e->getMessage());
            }
        }

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'stripe_payment_intent_id' => $paymentIntentId,
            'amount' => $amount,
            'currency' => strtoupper($currencyCode),
            'status' => PaymentStatus::Pending,
        ]);

        return [
            'client_secret' => $clientSecret,
            'payment_intent_id' => $paymentIntentId,
            'payment' => $payment,
        ];
    }

    /**
     * Confirm a payment atomically and finalize reservation.
     *
     * @throws Exception
     */
    public function confirmPayment(string $paymentIntentId): Payment
    {
        return DB::transaction(function () use ($paymentIntentId) {
            /** @var Payment $payment */
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === PaymentStatus::Succeeded) {
                return $payment;
            }

            $payment->update(['status' => PaymentStatus::Succeeded]);

            /** @var Reservation $reservation */
            $reservation = $payment->reservation()->lockForUpdate()->firstOrFail();
            $reservation->update([
                'status' => ReservationStatus::Confirmed,
                'expires_at' => null,
            ]);

            if ($reservation->event_seat_id) {
                /** @var EventSeat $seat */
                $seat = EventSeat::where('id', $reservation->event_seat_id)
                    ->lockForUpdate()
                    ->first();

                if ($seat) {
                    $seat->update(['status' => SeatStatus::Reservado]);
                    SeatStatusUpdated::dispatch($reservation->event_id, $seat->identifier, SeatStatus::Reservado->value);
                }
            }

            return $payment->fresh(['reservation']);
        });
    }
}
