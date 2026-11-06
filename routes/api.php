<?php

use App\Http\Controllers\Api\V1\Admin\AdminEventController;
use App\Http\Controllers\Api\V1\Admin\BuildingController;
use App\Http\Controllers\Api\V1\Admin\CampusController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\SpaceController;
use App\Http\Controllers\Api\V1\Admin\SpaceLayoutController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\EventTaquillaController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketScanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Públicas: Eventos y Cartelera
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::get('/events/{event}/seats', [EventController::class, 'seats']);

    // Públicas: Reservaciones y Boletos
    Route::post('/reservations/hold', [ReservationController::class, 'hold']);
    Route::post('/reservations/release', [ReservationController::class, 'release']);
    Route::post('/reservations/checkout', [ReservationController::class, 'checkout']);
    Route::get('/tickets/{token}', [TicketController::class, 'show']);

    // Protegidas: Validación de acceso (Escáner)
    Route::middleware(['auth:sanctum', 'role_or_permission:validador|admin|validate-tickets'])->group(function () {
        Route::post('/tickets/validate', [TicketScanController::class, 'validateToken']);
    });

    // Protegidas: Taquilla
    Route::middleware(['auth:sanctum', 'role_or_permission:taquilla|admin'])->group(function () {
        Route::get('/taquilla/events/{event}', [EventTaquillaController::class, 'show']);
        Route::patch('/taquilla/events/{event}/seats/{seat}', [EventTaquillaController::class, 'updateSeat']);
    });

    // Protegidas: Administración del Sistema
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::apiResource('campuses', CampusController::class);
        Route::apiResource('buildings', BuildingController::class);
        Route::apiResource('spaces', SpaceController::class);
        Route::put('spaces/{space}/layout', [SpaceLayoutController::class, 'updateLayout']);
        Route::apiResource('events', AdminEventController::class);
        Route::get('events/{event}/taquilla', [EventTaquillaController::class, 'show']);
        Route::patch('events/{event}/seats/{seat}', [EventTaquillaController::class, 'updateSeat']);
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
        Route::get('dashboard', [DashboardController::class, 'index']);
    });
});
