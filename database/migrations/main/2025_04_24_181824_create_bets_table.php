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
        Schema::create('bets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index(); // Usuário que fez a aposta
            $table->uuid('game_type_id')->index();        // Modalidade da aposta
            $table->json('numbers');                      // Números apostados
            $table->timestamps();

            // Foreign keys (opcional para simulação)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('game_type_id')->references('id')->on('game_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
