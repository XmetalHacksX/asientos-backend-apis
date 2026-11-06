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
        Schema::create('layout_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->string('type');
            $table->json('properties');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layout_objects');
    }
};
