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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('method'); // crypto, bank_transfer, mobile_money
            $table->decimal('amount', 12, 2);
            $table->string('crypto_name')->nullable();
            $table->string('crypto_address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('mobile_money_provider')->nullable(); // MTN, Orange, etc.
            $table->string('mobile_money_number')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
