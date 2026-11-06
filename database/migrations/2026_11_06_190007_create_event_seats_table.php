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
        Schema::create('event_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->nullOnDelete();
            $table->string('identifier', 50);
            $table->integer('pos_x');
            $table->integer('pos_y');
            $table->string('status', 50)->default('disponible');
            $table->timestamps();

            $table->unique(['event_id', 'identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_seats');
    }
};
