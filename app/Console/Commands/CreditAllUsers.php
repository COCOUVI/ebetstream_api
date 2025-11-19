<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class CreditAllUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:credit-all {amount=10000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Credit all users with a specified amount (default: 10,000)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $amount = (float) $this->argument('amount');
        
        $this->info("Starting to credit all users with {$amount} dollars...");
        
        $users = User::all();
        $totalUsers = $users->count();
        
        if ($totalUsers === 0) {
            $this->warn('No users found in the database.');
            return 0;
        }
        
        $this->info("Found {$totalUsers} users.");
        
        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();
        
        $credited = 0;
        $errors = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($users as $user) {
                try {
                    // Get or create wallet (in USD)
                    $wallet = Wallet::firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'balance' => 0,
                            'locked_balance' => 0,
                            'currency' => 'USD',
                        ]
                    );
                    
                    // Credit the wallet
                    $wallet->balance += $amount;
                    $wallet->save();
                    
                    $credited++;
                } catch (\Exception $e) {
                    $this->error("\nError crediting user ID {$user->id}: " . $e->getMessage());
                    $errors++;
                }
                
                $bar->advance();
            }
            
            DB::commit();
            
            $bar->finish();
            $this->newLine(2);
            $this->info("✓ Successfully credited {$credited} users with {$amount} dollars each.");
            
            if ($errors > 0) {
                $this->warn("⚠ {$errors} errors occurred during the process.");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $bar->finish();
            $this->newLine(2);
            $this->error("✗ An error occurred: " . $e->getMessage());
            return 1;
        }
    }
}
