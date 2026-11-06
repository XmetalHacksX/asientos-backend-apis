<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('node_id')->nullable()->nullOnDelete()->constrained('nodes');
            $table->foreignId('event_seat_id')->nullable()->nullOnDelete()->constrained('event_seats');
            $table->foreignId('user_id')->nullable()->nullOnDelete()->constrained('users');
            $table->foreignId('reserved_by_user_id')->nullable()->nullOnDelete()->constrained('users');
            $table->json('guest_info')->nullable();
            $table->string('status', 50)->default('pending');
            $table->dateTime('expires_at')->nullable();
            $table->uuid('ticket_token')->unique();
            $table->dateTime('validated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
