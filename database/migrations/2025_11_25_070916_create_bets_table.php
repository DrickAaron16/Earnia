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
        if (!Schema::hasTable('bets')) {
            Schema::create('bets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('game_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 14, 2);
                $table->decimal('potential_payout', 14, 2)->nullable();
                $table->enum('status', ['pending', 'won', 'lost', 'refunded', 'void'])->default('pending');
                $table->timestamp('placed_at')->useCurrent();
                $table->timestamp('resolved_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['game_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
