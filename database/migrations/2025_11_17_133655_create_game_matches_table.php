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
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->string('team1_name');
            $table->string('team2_name');
            $table->text('description')->nullable();
            $table->datetime('match_date');
            $table->enum('status', ['upcoming', 'live', 'finished', 'cancelled'])->default('upcoming');
            $table->string('result')->nullable(); // 'team1_win', 'draw', 'team2_win'
            $table->decimal('team1_odds', 5, 2)->default(1.00); // Cote pour victoire team1
            $table->decimal('draw_odds', 5, 2)->default(0.50); // Cote pour match nul
            $table->decimal('team2_odds', 5, 2)->default(1.00); // Cote pour victoire team2
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
