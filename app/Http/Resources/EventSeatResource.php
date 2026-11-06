<?php

namespace App\Http\Resources;

use App\Enums\SeatStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventSeatResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusValue = $this->status instanceof SeatStatus ? $this->status->value : $this->status;

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'user_id' => $this->user_id,
            'identifier' => $this->identifier,
            'pos_x' => $this->pos_x,
            'pos_y' => $this->pos_y,
            'status' => $statusValue,
        ];
    }
}
