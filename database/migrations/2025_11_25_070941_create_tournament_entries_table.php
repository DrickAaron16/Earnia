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
        Schema::create('tournament_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'active', 'eliminated', 'winner', 'refund_pending', 'refunded'])->default('pending');
            $table->unsignedInteger('seed')->nullable();
            $table->unsignedInteger('placement')->nullable();
            $table->decimal('payout_amount', 14, 2)->default(0);
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_entries');
    }
};
