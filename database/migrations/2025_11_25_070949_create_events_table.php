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
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->enum('type', ['raffle', 'wheel', 'flash_match', 'promotion']);
                $table->text('description')->nullable();
                $table->decimal('entry_fee', 14, 2)->default(0);
                $table->json('prize_structure')->nullable();
                $table->enum('status', ['draft', 'published', 'running', 'completed', 'cancelled'])->default('draft');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
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
        Schema::dropIfExists('events');
    }
};
