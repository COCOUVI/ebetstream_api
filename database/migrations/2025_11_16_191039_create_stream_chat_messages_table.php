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
        Schema::create('stream_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->string('type')->default('message'); // message, system, subscription, donation
            $table->boolean('is_moderator')->default(false);
            $table->boolean('is_subscriber')->default(false);
            $table->foreignId('reply_to')->nullable()->constrained('stream_chat_messages')->onDelete('set null');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
            
            $table->index(['stream_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_chat_messages');
    }
};
