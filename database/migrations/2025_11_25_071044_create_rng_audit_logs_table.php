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
        Schema::create('rng_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('seed');
            $table->string('hash');
            $table->string('algorithm')->default('sha256');
            $table->json('transcript')->nullable();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rng_audit_logs');
    }
};
