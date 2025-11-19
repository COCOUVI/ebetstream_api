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
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('username');
            $table->string('in_game_pseudo')->nullable()->after('full_name');
            $table->string('status')->nullable()->after('in_game_pseudo'); // Statut/Title
            $table->string('profile_photo')->nullable()->after('avatar'); // Photo de profil
            $table->text('qr_code')->nullable()->after('profile_photo'); // QR Code (base64 ou path)
            $table->string('profile_url')->nullable()->after('qr_code');
            $table->integer('tournaments_won')->default(0)->after('wins');
            $table->text('tournaments_list')->nullable()->after('tournaments_won'); // JSON
            $table->string('ranking')->nullable()->after('tournaments_list');
            $table->string('division')->nullable()->after('ranking');
            $table->integer('global_score')->default(0)->after('division');
            $table->string('current_season')->nullable()->after('global_score');
            $table->text('badges')->nullable()->after('current_season'); // JSON array
            $table->text('certifications')->nullable()->after('badges'); // JSON
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'full_name', 'in_game_pseudo', 'status', 'profile_photo', 'qr_code',
                'profile_url', 'tournaments_won', 'tournaments_list', 'ranking',
                'division', 'global_score', 'current_season', 'badges', 'certifications'
            ]);
        });
    }
};
