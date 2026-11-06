<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Enums\SeatStatus;
use App\Events\SeatStatusUpdated;
use App\Models\EventSeat;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:release-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release expired seat reservations and broadcast status updates via Reverb';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired seat holds...');

        $expiredReservations = Reservation::with('eventSeat')
            ->where('status', ReservationStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        if ($expiredReservations->isEmpty()) {
            $this->info('No expired reservations found.');

            return self::SUCCESS;
        }

        $releasedCount = 0;

        foreach ($expiredReservations as $reservation) {
            DB::transaction(function () use ($reservation, &$releasedCount) {
                $reservation->update(['status' => ReservationStatus::Cancelled]);

                if ($reservation->event_seat_id) {
                    $seat = EventSeat::where('id', $reservation->event_seat_id)
                        ->where('status', SeatStatus::Apartado)
                        ->lockForUpdate()
                        ->first();

                    if ($seat) {
                        $seat->update([
                            'status' => SeatStatus::Disponible,
                            'user_id' => null,
                        ]);

                        SeatStatusUpdated::dispatch(
                            $reservation->event_id,
                            $seat->identifier,
                            SeatStatus::Disponible->value
                        );

                        $releasedCount++;
                    }
                }
            });
        }

        $this->info("Successfully released {$releasedCount} expired seat reservations.");

        return self::SUCCESS;
    }
}
