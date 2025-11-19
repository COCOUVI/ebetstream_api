<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('opponent_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('game');
            $table->decimal('bet_amount', 12, 2);
            $table->enum('status', ['open', 'accepted', 'in_progress', 'completed', 'cancelled'])->default('open');

            $table->integer('creator_score')->nullable();
            $table->integer('opponent_score')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
