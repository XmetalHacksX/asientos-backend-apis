<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TicketController extends Controller
{
    /**
     * Show digital ticket information by token.
     */
    public function show(string $token): JsonResponse
    {
        $reservation = Reservation::with(['event.space.building.campus', 'eventSeat', 'user'])
            ->where('ticket_token', $token)
            ->first();

        if (! $reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Boleto no encontrado.',
            ], 404);
        }

        // Generate QR code SVG or payload
        $qrSvg = null;
        try {
            $qrSvg = (string) QrCode::format('svg')->size(250)->generate($token);
        } catch (\Throwable $e) {
            // Graceful fallback if svg generation has issues in certain environments
            $qrSvg = null;
        }

        return response()->json([
            'status' => 'success',
            'ticket' => new TicketResource($reservation),
            'qr_code_svg' => $qrSvg,
            'qr_value' => $token,
        ]);
    }
}
