<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('game');
            $table->decimal('entry_fee', 12, 2)->default(0);
            $table->decimal('reward', 12, 2);

            $table->enum('status', ['upcoming', 'ongoing', 'finished'])->default('upcoming');

            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();

            $table->timestamps();
        });

        Schema::create('tournament_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_user');
        Schema::dropIfExists('tournaments');
    }
};
