<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventSeat;
use App\Models\Node;
use Illuminate\Support\Facades\DB;

class EventSeatClonerService
{
    /**
     * Clone all active nodes of an event's space into event_seats.
     *
     * @return int Number of seats cloned
     */
    public function cloneSeatsForEvent(Event $event): int
    {
        return DB::transaction(function () use ($event) {
            $nodes = Node::where('space_id', $event->space_id)
                ->where('status', 'active')
                ->get();

            if ($nodes->isEmpty()) {
                return 0;
            }

            $now = now();
            $batch = [];

            foreach ($nodes as $node) {
                $batch[] = [
                    'event_id' => $event->id,
                    'user_id' => null,
                    'identifier' => $node->identifier,
                    'pos_x' => $node->pos_x,
                    'pos_y' => $node->pos_y,
                    'status' => 'disponible',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            EventSeat::insert($batch);

            return count($batch);
        });
    }
}
