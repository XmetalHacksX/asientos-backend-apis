<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\SeatStatus;
use App\Events\DashboardUpdated;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Event;
use App\Models\EventSeat;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Return live metrics and optionally broadcast to administrative clients.
     */
    public function index(Request $request): JsonResponse
    {
        $totalCampuses = Campus::count();
        $totalEvents = Event::count();
        $totalRevenue = (float) Payment::where('status', PaymentStatus::Succeeded)->sum('amount');
        $successfulPayments = Payment::where('status', PaymentStatus::Succeeded)->count();

        $totalSeats = EventSeat::count();
        $availableSeats = EventSeat::where('status', SeatStatus::Disponible)->count();
        $heldSeats = EventSeat::where('status', SeatStatus::Apartado)->count();
        $reservedSeats = EventSeat::where('status', SeatStatus::Reservado)->count();
        $blockedSeats = EventSeat::where('status', SeatStatus::Bloqueado)->count();

        $occupancyRate = $totalSeats > 0 ? round(($reservedSeats / $totalSeats) * 100, 2) : 0;

        $recentAdmissions = Reservation::with(['event', 'eventSeat'])
            ->where('status', ReservationStatus::Ingresado)
            ->orderBy('validated_at', 'desc')
            ->limit(5)
            ->get();

        $upcomingEvents = Event::with('space.building')
            ->where('start_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get();

        $metrics = [
            'overview' => [
                'campuses' => $totalCampuses,
                'events' => $totalEvents,
                'total_revenue' => $totalRevenue,
                'successful_payments' => $successfulPayments,
            ],
            'seats' => [
                'total' => $totalSeats,
                'disponibles' => $availableSeats,
                'apartados' => $heldSeats,
                'reservados' => $reservedSeats,
                'bloqueados' => $blockedSeats,
                'occupancy_rate_percentage' => $occupancyRate,
            ],
            'recent_admissions' => $recentAdmissions,
            'upcoming_events' => $upcomingEvents,
        ];

        if ($request->boolean('broadcast', false)) {
            DashboardUpdated::dispatch($metrics);
        }

        return response()->json([
            'status' => 'success',
            'data' => $metrics,
        ]);
    }
}
