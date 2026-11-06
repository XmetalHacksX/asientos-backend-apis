<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reservation\CheckoutRequest;
use App\Http\Requests\Api\V1\Reservation\HoldSeatRequest;
use App\Http\Requests\Api\V1\Reservation\ReleaseSeatRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationManager;
use App\Services\StripePaymentService;
use Exception;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(
        protected ReservationManager $reservationManager,
        protected StripePaymentService $stripePaymentService
    ) {}

    /**
     * Temporarily hold a seat.
     */
    public function hold(HoldSeatRequest $request): JsonResponse
    {
        try {
            $reservation = $this->reservationManager->holdSeat(
                seatId: (int) $request->seat_id,
                eventId: (int) $request->event_id,
                expiresInMinutes: config('reservations.hold_minutes', 10),
                userId: $request->user()?->id,
                guestInfo: $request->guest_info
            );

            $reservation->load(['event', 'eventSeat']);

            return response()->json([
                'status' => 'success',
                'message' => 'Asiento apartado temporalmente.',
                'data' => new ReservationResource($reservation),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Release a held seat back to available.
     */
    public function release(ReleaseSeatRequest $request): JsonResponse
    {
        try {
            $this->reservationManager->releaseSeat(
                seatId: (int) $request->seat_id,
                eventId: (int) $request->event_id
            );

            return response()->json([
                'status' => 'success',
                'message' => 'El asiento ha sido liberado exitosamente.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process checkout for a reservation (payment intent creation or direct confirmation).
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        try {
            /** @var Reservation $reservation */
            $reservation = null;

            if ($request->reservation_id) {
                $reservation = Reservation::findOrFail($request->reservation_id);
            } elseif ($request->ticket_token) {
                $reservation = Reservation::where('ticket_token', $request->ticket_token)->firstOrFail();
            }

            if (! $reservation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se encontró la reservación indicada.',
                ], 404);
            }

            // Check if reservation is expired
            if ($reservation->status === ReservationStatus::Pending && $reservation->expires_at && $reservation->expires_at->isPast()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El tiempo de apartado de este asiento ha expirado. Por favor selecciónalo nuevamente.',
                ], 422);
            }

            if ($request->guest_info) {
                $reservation->update(['guest_info' => $request->guest_info]);
            }

            $amount = (float) ($request->amount ?? config('reservations.default_ticket_price', 150.00));
            $currency = config('reservations.default_currency', 'MXN');
            $paymentMethod = $request->payment_method ?? 'stripe';

            if ($paymentMethod === 'stripe') {
                if ($request->payment_intent_id) {
                    $payment = $this->stripePaymentService->confirmPayment($request->payment_intent_id);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Pago confirmado exitosamente.',
                        'data' => [
                            'payment' => $payment,
                            'reservation' => new ReservationResource($reservation->fresh(['event', 'eventSeat'])),
                        ],
                    ]);
                }

                $intentData = $this->stripePaymentService->createPaymentIntent($reservation, $amount, $currency);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Intento de pago creado.',
                    'client_secret' => $intentData['client_secret'],
                    'payment_intent_id' => $intentData['payment_intent_id'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'reservation' => new ReservationResource($reservation->fresh(['event', 'eventSeat'])),
                ]);
            }

            // Direct checkout (box office / cash / admin bypass)
            $confirmedReservation = $this->reservationManager->reserveDirectly(
                seatId: $reservation->event_seat_id,
                eventId: $reservation->event_id,
                guestInfo: $request->guest_info ?? $reservation->guest_info,
                userId: $request->user()?->id ?? $reservation->user_id,
                reservedByUserId: $request->user()?->id
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Boleto reservado y confirmado exitosamente.',
                'data' => new ReservationResource($confirmedReservation->load(['event', 'eventSeat'])),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
