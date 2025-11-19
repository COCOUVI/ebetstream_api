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
        Schema::create('stream_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('status')->default('starting'); // starting, live, ended
            $table->integer('peak_viewers')->default(0);
            $table->integer('total_viewers')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('metadata')->nullable(); // Quality, bitrate, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_sessions');
    }
};
