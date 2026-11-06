<?php

use App\Enums\ReservationStatus;
use App\Enums\SeatStatus;
use App\Events\SeatStatusUpdated;
use App\Models\Event;
use App\Models\EventSeat;
use App\Models\Reservation;
use Database\Seeders\CampusAndBuildingSeeder;
use Database\Seeders\EventSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SpaceAndLayoutSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;

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

test('public can view active events and seat layout', function () {
    $event = Event::first();

    $response = $this->getJson('/api/v1/events');
    $response->assertStatus(200)
        ->assertJsonFragment(['name' => $event->name]);

    $detailResponse = $this->getJson("/api/v1/events/{$event->id}");
    $detailResponse->assertStatus(200)
        ->assertJsonFragment(['name' => $event->name]);

    $seatsResponse = $this->getJson("/api/v1/events/{$event->id}/seats");
    $seatsResponse->assertStatus(200)
        ->assertJsonStructure([
            'event_id',
            'event_name',
            'total_seats',
            'available_seats',
            'seats',
        ]);

    expect($seatsResponse->json('total_seats'))->toBe(100);
});

test('user can hold a seat and real-time event is broadcast', function () {
    EventFacade::fake([SeatStatusUpdated::class]);

    $event = Event::first();
    $seat = EventSeat::where('event_id', $event->id)->where('status', SeatStatus::Disponible)->first();

    $response = $this->postJson('/api/v1/reservations/hold', [
        'event_id' => $event->id,
        'seat_id' => $seat->id,
        'guest_info' => [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '9831234567',
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['status' => 'pending']);

    expect($seat->fresh()->status)->toBe(SeatStatus::Apartado);

    EventFacade::assertDispatched(SeatStatusUpdated::class, function ($event) use ($seat) {
        return $event->seatIdentifier === $seat->identifier && $event->status === 'apartado';
    });
});

test('conflicting hold request on already held seat fails', function () {
    $event = Event::first();
    $seat = EventSeat::where('event_id', $event->id)->first();

    // First hold
    $this->postJson('/api/v1/reservations/hold', [
        'event_id' => $event->id,
        'seat_id' => $seat->id,
    ])->assertStatus(201);

    // Second hold on same seat
    $conflictResponse = $this->postJson('/api/v1/reservations/hold', [
        'event_id' => $event->id,
        'seat_id' => $seat->id,
    ]);

    $conflictResponse->assertStatus(422)
        ->assertJsonFragment(['status' => 'error']);
});

test('held seat can be released back to disponible', function () {
    EventFacade::fake([SeatStatusUpdated::class]);

    $event = Event::first();
    $seat = EventSeat::where('event_id', $event->id)->first();

    $this->postJson('/api/v1/reservations/hold', [
        'event_id' => $event->id,
        'seat_id' => $seat->id,
    ]);

    $releaseResponse = $this->postJson('/api/v1/reservations/release', [
        'event_id' => $event->id,
        'seat_id' => $seat->id,
    ]);

    $releaseResponse->assertStatus(200)
        ->assertJson(['status' => 'success']);

    expect($seat->fresh()->status)->toBe(SeatStatus::Disponible);

    EventFacade::assertDispatched(SeatStatusUpdated::class, function ($event) use ($seat) {
        return $event->seatIdentifier === $seat->identifier && $event->status === 'disponible';
    });
});

test('user can complete checkout to confirm reservation and seat', function () {
    $event = Event::first();
    $seat = EventSeat::where('event_id', $event->id)->first();

    $holdResponse = $this->postJson('/api/v1/reservations/hold', [
        'event_id' => $event->id,
        'seat_id' => $seat->id,
        'guest_info' => ['name' => 'Maria Lopez'],
    ]);

    $reservationId = $holdResponse->json('data.id');

    // Checkout with direct method
    $checkoutResponse = $this->postJson('/api/v1/reservations/checkout', [
        'reservation_id' => $reservationId,
        'payment_method' => 'direct',
    ]);

    $checkoutResponse->assertStatus(200)
        ->assertJsonFragment(['status' => 'confirmed']);

    expect($seat->fresh()->status)->toBe(SeatStatus::Reservado);
});

test('expired reservations command frees held seats', function () {
    $event = Event::first();
    $seat = EventSeat::where('event_id', $event->id)->first();

    $seat->update(['status' => SeatStatus::Apartado]);
    Reservation::create([
        'event_id' => $event->id,
        'event_seat_id' => $seat->id,
        'status' => ReservationStatus::Pending,
        'expires_at' => now()->subMinutes(15), // expired 15 mins ago
    ]);

    $this->artisan('reservations:release-expired')
        ->assertExitCode(0);

    expect($seat->fresh()->status)->toBe(SeatStatus::Disponible);
});
