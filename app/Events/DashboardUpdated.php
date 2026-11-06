<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $metrics
     */
    public function __construct(
        public array $metrics
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'metrics' => $this->metrics,
        ];
    }
}
