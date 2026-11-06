<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SeatStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\EventSeatResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    /**
     * Display a listing of upcoming active events.
     */
    public function index(): AnonymousResourceCollection
    {
        $events = Event::with(['space.building.campus'])
            ->orderBy('start_time', 'asc')
            ->get();

        return EventResource::collection($events);
    }

    /**
     * Display the specified event with space layout details.
     */
    public function show(Event $event): EventResource
    {
        $event->load(['space.building.campus', 'space.layoutObjects']);

        return new EventResource($event);
    }

    /**
     * Get operational seat map for an event.
     */
    public function seats(Event $event): JsonResponse
    {
        $seats = $event->eventSeats()->orderBy('identifier', 'asc')->get();

        return response()->json([
            'event_id' => $event->id,
            'event_name' => $event->name,
            'total_seats' => $seats->count(),
            'available_seats' => $seats->where('status', SeatStatus::Disponible)->count(),
            'seats' => EventSeatResource::collection($seats),
        ]);
    }
}
