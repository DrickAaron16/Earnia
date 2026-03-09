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
        Schema::create('game_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game_name');
            $table->string('game_slug');
            $table->integer('score')->default(0);
            $table->integer('total_rounds')->default(0);
            $table->string('difficulty')->default('Normal');
            $table->decimal('stake', 10, 2)->nullable();
            $table->decimal('reward', 10, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('status')->default('completed'); // 'won', 'lost', 'completed'
            $table->timestamp('played_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('played_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_history');
    }
};

