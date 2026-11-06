<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;

class TicketValidatorService
{
    /**
     * Validate a QR ticket token and register admission.
     *
     * @return array{
     *     valid: bool,
     *     status: string,
     *     message: string,
     *     reservation: ?Reservation,
     *     validated_at: ?string
     * }
     */
    public function validateTicket(string $ticketToken): array
    {
        $reservation = Reservation::with(['event', 'eventSeat', 'user'])
            ->where('ticket_token', $ticketToken)
            ->first();

        if (! $reservation) {
            return [
                'valid' => false,
                'status' => 'not_found',
                'message' => 'El código de boleto proporcionado no existe en el sistema.',
                'reservation' => null,
                'validated_at' => null,
            ];
        }

        if ($reservation->status === ReservationStatus::Ingresado) {
            return [
                'valid' => false,
                'status' => 'already_used',
                'message' => 'Advertencia: Este boleto ya fue utilizado previamente el '.($reservation->validated_at ? $reservation->validated_at->format('d/m/Y H:i:s') : 'N/A'),
                'reservation' => $reservation,
                'validated_at' => $reservation->validated_at?->toIso8601String(),
            ];
        }

        if ($reservation->status === ReservationStatus::Cancelled) {
            return [
                'valid' => false,
                'status' => 'cancelled',
                'message' => 'El boleto ha sido cancelado.',
                'reservation' => $reservation,
                'validated_at' => null,
            ];
        }

        if ($reservation->status !== ReservationStatus::Confirmed) {
            return [
                'valid' => false,
                'status' => 'unconfirmed',
                'message' => 'El boleto no se encuentra confirmado ni pagado (Estado: '.$reservation->status->value.').',
                'reservation' => $reservation,
                'validated_at' => null,
            ];
        }

        // Stamp entry
        $now = now();
        $reservation->update([
            'status' => ReservationStatus::Ingresado,
            'validated_at' => $now,
        ]);

        return [
            'valid' => true,
            'status' => 'ingresado',
            'message' => 'Acceso permitido. Bienvenido al evento.',
            'reservation' => $reservation->fresh(['event', 'eventSeat', 'user']),
            'validated_at' => $now->toIso8601String(),
        ];
    }
}
