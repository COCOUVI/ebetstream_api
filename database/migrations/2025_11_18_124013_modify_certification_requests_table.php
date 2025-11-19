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
        Schema::table('certification_requests', function (Blueprint $table) {
            // Supprimer les anciens champs
            $table->dropColumn(['social_network_url', 'notes']);
            
            // Ajouter les nouveaux champs
            $table->enum('id_type', ['passport', 'national_id', 'driving_license', 'residence_permit'])->nullable()->after('date_of_birth');
            $table->string('id_number')->nullable()->after('id_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certification_requests', function (Blueprint $table) {
            // Restaurer les anciens champs
            $table->string('social_network_url')->nullable()->after('date_of_birth');
            $table->text('notes')->nullable()->after('social_network_url');
            
            // Supprimer les nouveaux champs
            $table->dropColumn(['id_type', 'id_number']);
        });
    }
};
