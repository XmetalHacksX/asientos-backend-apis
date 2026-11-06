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
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->string('identifier', 50);
            $table->integer('pos_x');
            $table->integer('pos_y');
            $table->string('status', 50)->default('active');
            $table->boolean('is_occupied')->default(false);
            $table->timestamps();

            $table->unique(['space_id', 'identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
