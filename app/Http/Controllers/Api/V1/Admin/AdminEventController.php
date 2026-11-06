<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventSeatClonerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminEventController extends Controller
{
    public function __construct(
        protected EventSeatClonerService $clonerService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return EventResource::collection(Event::with(['space.building.campus'])->orderBy('start_time', 'desc')->get());
    }

    public function store(EventRequest $request): JsonResponse
    {
        $event = Event::create($request->validated());

        // Clone seats automatically unless explicitly opted out
        $clonedCount = 0;
        if ($request->boolean('clone_seats', true)) {
            $clonedCount = $this->clonerService->cloneSeatsForEvent($event);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Evento creado exitosamente. Se clonaron {$clonedCount} asientos operativos.",
            'data' => new EventResource($event->load(['space.building.campus', 'eventSeats'])),
        ], 201);
    }

    public function show(Event $event): EventResource
    {
        return new EventResource($event->load(['space.building.campus', 'space.layoutObjects', 'eventSeats']));
    }

    public function update(EventRequest $request, Event $event): JsonResponse
    {
        $event->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Evento actualizado exitosamente.',
            'data' => new EventResource($event->load(['space.building.campus'])),
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Evento eliminado correctamente.',
        ]);
    }
}
