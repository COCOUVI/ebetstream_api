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
        // Update all existing wallets to USD
        \DB::table('wallets')->update(['currency' => 'USD']);
        
        // Remove duplicate wallets, keeping only the first one for each user
        $duplicates = \DB::table('wallets')
            ->select('user_id', \DB::raw('COUNT(*) as count'), \DB::raw('MIN(id) as keep_id'))
            ->groupBy('user_id')
            ->having('count', '>', 1)
            ->get();
        
        foreach ($duplicates as $duplicate) {
            // Get all wallet IDs for this user except the one to keep
            $walletIdsToDelete = \DB::table('wallets')
                ->where('user_id', $duplicate->user_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->pluck('id');
            
            // Sum balances from wallets to delete and add to the kept wallet
            $totalBalance = \DB::table('wallets')
                ->whereIn('id', $walletIdsToDelete)
                ->sum('balance');
            
            $totalLockedBalance = \DB::table('wallets')
                ->whereIn('id', $walletIdsToDelete)
                ->sum('locked_balance');
            
            // Update the kept wallet with combined balances
            \DB::table('wallets')
                ->where('id', $duplicate->keep_id)
                ->update([
                    'balance' => \DB::raw("balance + {$totalBalance}"),
                    'locked_balance' => \DB::raw("locked_balance + {$totalLockedBalance}"),
                ]);
            
            // Delete duplicate wallets
            \DB::table('wallets')->whereIn('id', $walletIdsToDelete)->delete();
        }
        
        // Add unique constraint on user_id to ensure one wallet per user
        Schema::table('wallets', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });
    }
};
