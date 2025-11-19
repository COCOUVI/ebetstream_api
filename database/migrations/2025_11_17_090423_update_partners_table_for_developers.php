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
        Schema::table('partners', function (Blueprint $table) {
            $table->string('specialty')->nullable()->after('name');
            $table->string('avatar')->nullable()->after('logo');
            $table->text('bio')->nullable()->after('website');
            $table->string('country')->nullable()->after('bio');
            $table->integer('position')->default(0)->after('country');
            $table->boolean('is_active')->default(true)->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn(['specialty', 'avatar', 'bio', 'country', 'position', 'is_active']);
        });
    }
};
