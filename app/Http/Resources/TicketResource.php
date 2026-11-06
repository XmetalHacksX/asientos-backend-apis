<?php

namespace App\Http\Resources;

use App\Enums\ReservationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusValue = $this->status instanceof ReservationStatus ? $this->status->value : $this->status;

        return [
            'ticket_token' => $this->ticket_token,
            'status' => $statusValue,
            'event' => [
                'id' => $this->event?->id,
                'name' => $this->event?->name,
                'start_time' => $this->event?->start_time?->toIso8601String(),
                'space' => $this->event?->space?->name,
                'building' => $this->event?->space?->building?->name,
                'campus' => $this->event?->space?->building?->campus?->name,
            ],
            'seat' => [
                'id' => $this->eventSeat?->id,
                'identifier' => $this->eventSeat?->identifier,
            ],
            'guest_info' => $this->guest_info,
            'validated_at' => $this->validated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
