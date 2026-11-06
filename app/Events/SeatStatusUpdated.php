<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeatStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $eventId,
        public string $seatIdentifier,
        public string $status
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('event.'.$this->eventId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'seat.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'eventId' => $this->eventId,
            'seatIdentifier' => $this->seatIdentifier,
            'status' => $this->status,
        ];
    }
}
