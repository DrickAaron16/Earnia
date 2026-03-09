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
        Schema::create('matchmaking_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('mode', ['solo', 'duel', 'multiplayer'])->default('duel');
            $table->decimal('stake_amount', 14, 2);
            $table->unsignedTinyInteger('max_players')->default(2);
            $table->enum('status', ['waiting', 'matched', 'expired', 'cancelled'])->default('waiting');
            $table->unsignedInteger('skill_rating')->nullable();
            $table->json('filters')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'mode', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matchmaking_tickets');
    }
};
