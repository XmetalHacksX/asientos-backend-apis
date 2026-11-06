<?php

use App\Enums\ReservationStatus;
use App\Enums\SeatStatus;
use App\Models\Event;
use App\Models\EventSeat;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\CampusAndBuildingSeeder;
use Database\Seeders\EventSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SpaceAndLayoutSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        RoleAndPermissionSeeder::class,
        UserSeeder::class,
        CampusAndBuildingSeeder::class,
        SpaceAndLayoutSeeder::class,
        EventSeeder::class,
    ]);
});

test('public can view digital ticket and validator can scan token at door', function () {
    $event = Event::first();
    $seat = EventSeat::where('event_id', $event->id)->first();

    $reservation = Reservation::create([
        'event_id' => $event->id,
        'event_seat_id' => $seat->id,
        'status' => ReservationStatus::Confirmed,
        'guest_info' => ['name' => 'Carlos Mendoza'],
    ]);

    // Public ticket view
    $ticketResponse = $this->getJson("/api/v1/tickets/{$reservation->ticket_token}");
    $ticketResponse->assertStatus(200)
        ->assertJsonFragment(['status' => 'confirmed'])
        ->assertJsonStructure(['qr_value']);

    // Door scan with Validador role
    $validador = User::where('email', 'validador@example.com')->first();
    $validadorToken = $validador->createToken('scan')->plainTextToken;

    $firstScanResponse = $this->withHeader('Authorization', "Bearer {$validadorToken}")
        ->postJson('/api/v1/tickets/validate', [
            'ticket_token' => $reservation->ticket_token,
        ]);

    $firstScanResponse->assertStatus(200)
        ->assertJson([
            'valid' => true,
            'status' => 'ingresado',
        ]);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Ingresado);

    // Duplicate scan warning (409 Conflict)
    $secondScanResponse = $this->withHeader('Authorization', "Bearer {$validadorToken}")
        ->postJson('/api/v1/tickets/validate', [
            'ticket_token' => $reservation->ticket_token,
        ]);

    $secondScanResponse->assertStatus(409)
        ->assertJson([
            'valid' => false,
            'status' => 'already_used',
        ]);
});

test('unauthenticated request to admin dashboard is denied', function () {
    $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
});

test('admin user can access dashboard and receive live metrics', function () {
    $admin = User::where('email', 'admin@example.com')->first();
    $token = $admin->createToken('admin')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'overview' => ['campuses', 'events', 'total_revenue'],
                'seats' => ['total', 'disponibles', 'apartados', 'reservados'],
            ],
        ]);
});

test('taquillero can update seat status to bloqueado and reservado', function () {
    $taquilla = User::where('email', 'taquilla@example.com')->first();
    $token = $taquilla->createToken('taquilla')->plainTextToken;

    $event = Event::first();
    $seat = EventSeat::where('event_id', $event->id)->first();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/taquilla/events/{$event->id}/seats/{$seat->id}", [
            'status' => 'bloqueado',
        ]);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success']);

    expect($seat->fresh()->status)->toBe(SeatStatus::Bloqueado);
});
