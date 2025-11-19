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
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('game_match_id')->constrained('game_matches')->onDelete('cascade');
            $table->enum('bet_type', ['team1_win', 'draw', 'team2_win']);
            $table->decimal('amount', 10, 2); // Montant parié
            $table->decimal('potential_win', 10, 2); // Gain potentiel
            $table->enum('status', ['pending', 'won', 'lost', 'cancelled'])->default('pending');
            $table->timestamps();
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
