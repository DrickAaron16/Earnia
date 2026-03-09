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
        if (!Schema::hasTable('games')) {
            Schema::create('games', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->unsignedTinyInteger('min_players')->default(1);
                $table->unsignedTinyInteger('max_players')->default(2);
                $table->enum('default_mode', ['solo', 'duel', 'multiplayer'])->default('solo');
                $table->decimal('min_stake', 14, 2)->default(0);
                $table->decimal('max_stake', 14, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('requires_rng')->default(false);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
