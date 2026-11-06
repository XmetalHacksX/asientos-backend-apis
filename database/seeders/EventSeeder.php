<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Space;
use App\Services\EventSeatClonerService;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $space = Space::where('name', 'Teatro Principal de las Artes')->first();

        if (! $space) {
            return;
        }

        $event = Event::firstOrCreate(
            [
                'space_id' => $space->id,
                'name' => 'Gran Gala Concierto Sinfónico 2026',
            ],
            [
                'description' => 'Presentación estelar de la Orquesta Filarmónica con más de 80 músicos en escena.',
                'poster_url' => 'https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1200&q=80',
                'start_time' => now()->addDays(7)->setTime(20, 0, 0),
                'end_time' => now()->addDays(7)->setTime(22, 30, 0),
            ]
        );

        // Clone seats to event_seats if not already cloned
        if ($event->eventSeats()->count() === 0) {
            $cloner = app(EventSeatClonerService::class);
            $cloner->cloneSeatsForEvent($event);
        }
    }
}
