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
        Schema::table('streams', function (Blueprint $table) {
            $table->string('title')->nullable()->after('user_id');
            $table->text('description')->nullable()->after('title');
            $table->string('stream_key')->unique()->nullable()->after('description');
            $table->string('rtmp_url')->nullable()->after('stream_key');
            $table->string('hls_url')->nullable()->after('rtmp_url');
            $table->string('thumbnail')->nullable()->after('hls_url');
            $table->string('category')->nullable()->after('thumbnail');
            $table->string('game')->nullable()->after('category');
            $table->integer('viewer_count')->default(0)->after('viewers');
            $table->integer('follower_count')->default(0)->after('viewer_count');
            $table->timestamp('started_at')->nullable()->after('is_live');
            $table->timestamp('ended_at')->nullable()->after('started_at');
            $table->json('settings')->nullable()->after('ended_at');
            $table->dropColumn(['platform', 'url', 'viewers']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->string('platform');
            $table->string('url');
            $table->integer('viewers')->default(0);
            $table->dropColumn([
                'title', 'description', 'stream_key', 'rtmp_url', 'hls_url',
                'thumbnail', 'category', 'game', 'viewer_count', 'follower_count',
                'started_at', 'ended_at', 'settings'
            ]);
        });
    }
};
