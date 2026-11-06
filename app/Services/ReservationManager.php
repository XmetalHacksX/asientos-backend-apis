<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\SeatStatus;
use App\Events\SeatStatusUpdated;
use App\Models\EventSeat;
use App\Models\Node;
use App\Models\Reservation;
use Exception;
use Illuminate\Support\Facades\DB;

class ReservationManager
{
    /**
     * Place a temporary hold on a seat for an event (default 10 minutes).
     *
     * @throws Exception
     */
    public function holdSeat(
        int $seatId,
        int $eventId,
        ?int $expiresInMinutes = null,
        ?int $userId = null,
        ?array $guestInfo = null
    ): Reservation {
        $minutes = $expiresInMinutes ?? config('reservations.hold_minutes', 10);

        return DB::transaction(function () use ($seatId, $eventId, $minutes, $userId, $guestInfo) {
            /** @var EventSeat $seat */
            $seat = EventSeat::where('id', $seatId)
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->firstOrFail();

            // Self-healing: if seat is in 'apartado', check if the reservation is already expired
            if ($seat->status === SeatStatus::Apartado) {
                $existingReservation = Reservation::where('event_seat_id', $seat->id)
                    ->where('status', ReservationStatus::Pending)
                    ->latest()
                    ->first();

                if ($existingReservation && $existingReservation->expires_at && $existingReservation->expires_at->isPast()) {
                    $existingReservation->update(['status' => ReservationStatus::Cancelled]);
                    $seat->status = SeatStatus::Disponible;
                } else {
                    throw new Exception('El asiento ya se encuentra apartado temporalmente por otro usuario.');
                }
            }

            if ($seat->status === SeatStatus::Reservado) {
                throw new Exception('El asiento ya ha sido reservado definitivamente.');
            }

            if ($seat->status === SeatStatus::Bloqueado) {
                throw new Exception('El asiento se encuentra bloqueado por administración.');
            }

            // Find matching node template if exists
            $node = Node::where('identifier', $seat->identifier)
                ->whereHas('space.events', function ($query) use ($eventId) {
                    $query->where('id', $eventId);
                })
                ->first();

            // Update seat to 'apartado'
            $seat->update([
                'status' => SeatStatus::Apartado,
                'user_id' => $userId,
            ]);

            // Create pending reservation
            $reservation = Reservation::create([
                'event_id' => $eventId,
                'event_seat_id' => $seat->id,
                'node_id' => $node?->id,
                'user_id' => $userId,
                'reserved_by_user_id' => $userId,
                'guest_info' => $guestInfo,
                'status' => ReservationStatus::Pending,
                'expires_at' => now()->addMinutes($minutes),
            ]);

            // Broadcast real-time websocket event
            SeatStatusUpdated::dispatch($eventId, $seat->identifier, SeatStatus::Apartado->value);

            return $reservation;
        });
    }

    /**
     * Release a held seat back to 'disponible'.
     *
     * @throws Exception
     */
    public function releaseSeat(int $seatId, int $eventId): bool
    {
        return DB::transaction(function () use ($seatId, $eventId) {
            /** @var EventSeat $seat */
            $seat = EventSeat::where('id', $seatId)
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($seat->status === SeatStatus::Reservado) {
                throw new Exception('No se puede liberar un asiento que ya ha sido pagado y reservado.');
            }

            // Cancel any pending reservations
            Reservation::where('event_seat_id', $seat->id)
                ->where('status', ReservationStatus::Pending)
                ->update(['status' => ReservationStatus::Cancelled]);

            $seat->update([
                'status' => SeatStatus::Disponible,
                'user_id' => null,
            ]);

            SeatStatusUpdated::dispatch($eventId, $seat->identifier, SeatStatus::Disponible->value);

            return true;
        });
    }

    /**
     * Reserve a seat directly (e.g. box office or after payment).
     *
     * @throws Exception
     */
    public function reserveDirectly(
        int $seatId,
        int $eventId,
        ?array $guestInfo = null,
        ?int $userId = null,
        ?int $reservedByUserId = null
    ): Reservation {
        return DB::transaction(function () use ($seatId, $eventId, $guestInfo, $userId, $reservedByUserId) {
            /** @var EventSeat $seat */
            $seat = EventSeat::where('id', $seatId)
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($seat->status === SeatStatus::Reservado) {
                throw new Exception('El asiento ya se encuentra reservado.');
            }

            if ($seat->status === SeatStatus::Bloqueado) {
                throw new Exception('El asiento está bloqueado para el público.');
            }

            // Check if there is an existing pending reservation for this seat
            $reservation = Reservation::where('event_seat_id', $seat->id)
                ->where('status', ReservationStatus::Pending)
                ->latest()
                ->first();

            $node = Node::where('identifier', $seat->identifier)
                ->whereHas('space.events', function ($query) use ($eventId) {
                    $query->where('id', $eventId);
                })
                ->first();

            if ($reservation) {
                $reservation->update([
                    'user_id' => $userId ?? $reservation->user_id,
                    'reserved_by_user_id' => $reservedByUserId ?? $reservation->reserved_by_user_id,
                    'guest_info' => $guestInfo ?? $reservation->guest_info,
                    'status' => ReservationStatus::Confirmed,
                    'expires_at' => null,
                ]);
            } else {
                $reservation = Reservation::create([
                    'event_id' => $eventId,
                    'event_seat_id' => $seat->id,
                    'node_id' => $node?->id,
                    'user_id' => $userId,
                    'reserved_by_user_id' => $reservedByUserId,
                    'guest_info' => $guestInfo,
                    'status' => ReservationStatus::Confirmed,
                    'expires_at' => null,
                ]);
            }

            $seat->update([
                'status' => SeatStatus::Reservado,
                'user_id' => $userId,
            ]);

            SeatStatusUpdated::dispatch($eventId, $seat->identifier, SeatStatus::Reservado->value);

            return $reservation->fresh();
        });
    }
}
