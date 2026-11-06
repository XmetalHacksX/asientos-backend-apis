<?php

namespace App\Http\Resources;

use App\Enums\ReservationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusValue = $this->status instanceof ReservationStatus ? $this->status->value : $this->status;

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'node_id' => $this->node_id,
            'event_seat_id' => $this->event_seat_id,
            'user_id' => $this->user_id,
            'reserved_by_user_id' => $this->reserved_by_user_id,
            'guest_info' => $this->guest_info,
            'status' => $statusValue,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'ticket_token' => $this->ticket_token,
            'validated_at' => $this->validated_at?->toIso8601String(),
            'event' => new EventResource($this->whenLoaded('event')),
            'seat' => new EventSeatResource($this->whenLoaded('eventSeat')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
