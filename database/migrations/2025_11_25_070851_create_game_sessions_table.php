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
        if (!Schema::hasTable('game_sessions')) {
            Schema::create('game_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('game_id')->constrained()->cascadeOnDelete();
                $table->foreignId('host_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('mode', ['solo', 'duel', 'multiplayer'])->default('solo');
                $table->decimal('stake_amount', 14, 2)->default(0);
                $table->decimal('pot_amount', 14, 2)->default(0);
                $table->enum('status', ['waiting', 'matching', 'in_progress', 'completed', 'cancelled'])->default('waiting');
                $table->unsignedTinyInteger('max_players')->default(2);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->string('rng_seed')->nullable();
                $table->string('rng_signature')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
