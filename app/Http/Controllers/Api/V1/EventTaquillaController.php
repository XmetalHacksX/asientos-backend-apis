<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SeatStatus;
use App\Events\SeatStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateSeatRequest;
use App\Http\Resources\EventSeatResource;
use App\Models\Event;
use App\Models\EventSeat;
use App\Services\ReservationManager;
use Illuminate\Http\JsonResponse;

class EventTaquillaController extends Controller
{
    public function __construct(
        protected ReservationManager $reservationManager
    ) {}

    /**
     * Show box office operational view of the event.
     */
    public function show(Event $event): JsonResponse
    {
        $seats = $event->eventSeats()->with('reservation')->get();

        return response()->json([
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'start_time' => $event->start_time?->toIso8601String(),
            ],
            'summary' => [
                'total' => $seats->count(),
                'disponibles' => $seats->where('status', SeatStatus::Disponible)->count(),
                'apartados' => $seats->where('status', SeatStatus::Apartado)->count(),
                'reservados' => $seats->where('status', SeatStatus::Reservado)->count(),
                'bloqueados' => $seats->where('status', SeatStatus::Bloqueado)->count(),
            ],
            'seats' => EventSeatResource::collection($seats),
        ]);
    }

    /**
     * Update seat status from box office (block, free, reserve).
     */
    public function updateSeat(UpdateSeatRequest $request, Event $event, EventSeat $seat): JsonResponse
    {
        if ($seat->event_id !== $event->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'El asiento no pertenece al evento especificado.',
            ], 422);
        }

        $newStatus = SeatStatus::from($request->status);

        if ($newStatus === SeatStatus::Reservado) {
            $reservation = $this->reservationManager->reserveDirectly(
                seatId: $seat->id,
                eventId: $event->id,
                guestInfo: $request->guest_info,
                userId: $request->user_id ?? $request->user()?->id,
                reservedByUserId: $request->user()?->id
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Asiento vendido y reservado exitosamente en taquilla.',
                'seat' => new EventSeatResource($seat->fresh()),
                'reservation' => $reservation,
            ]);
        }

        if ($newStatus === SeatStatus::Disponible) {
            $this->reservationManager->releaseSeat($seat->id, $event->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Asiento liberado y puesto a disposición general.',
                'seat' => new EventSeatResource($seat->fresh()),
            ]);
        }

        // Bloqueado u otro estado administrativo
        $seat->update([
            'status' => $newStatus,
            'user_id' => $request->user_id,
        ]);

        SeatStatusUpdated::dispatch($event->id, $seat->identifier, $newStatus->value);

        return response()->json([
            'status' => 'success',
            'message' => "Estado del asiento actualizado a '{$newStatus->value}'.",
            'seat' => new EventSeatResource($seat->fresh()),
        ]);
    }
}
