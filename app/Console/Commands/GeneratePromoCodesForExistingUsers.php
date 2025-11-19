<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class GeneratePromoCodesForExistingUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:generate-promo-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate promo codes for existing users who do not have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting to generate promo codes for existing users...");
        
        // Trouver tous les utilisateurs sans code promo
        $users = User::whereNull('promo_code')
            ->orWhere('promo_code', '')
            ->get();
        
        $totalUsers = $users->count();
        
        if ($totalUsers === 0) {
            $this->info('✓ All users already have promo codes.');
            return 0;
        }
        
        $this->info("Found {$totalUsers} users without promo codes.");
        
        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();
        
        $generated = 0;
        $errors = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($users as $user) {
                try {
                    $promoCode = $this->generateUniquePromoCode($user);
                    
                    $user->promo_code = $promoCode;
                    $user->saveQuietly(); // Utilise saveQuietly pour éviter les observers
                    
                    $generated++;
                } catch (\Exception $e) {
                    $this->error("\nError generating promo code for user ID {$user->id}: " . $e->getMessage());
                    $errors++;
                }
                
                $bar->advance();
            }
            
            DB::commit();
            
            $bar->finish();
            $this->newLine(2);
            $this->info("✓ Successfully generated promo codes for {$generated} users.");
            
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

    /**
     * Génère un code promo unique basé sur le username de l'utilisateur
     */
    private function generateUniquePromoCode(User $user): string
    {
        // Génère un code basé sur le username (majuscules) + un suffixe aléatoire
        $baseCode = strtoupper(Str::slug($user->username, ''));
        
        // Si le username est vide ou trop court, utiliser l'ID
        if (empty($baseCode) || strlen($baseCode) < 3) {
            $baseCode = 'USER' . $user->id;
        }
        
        $suffix = Str::random(4);
        $promoCode = $baseCode . $suffix;

        // Vérifie que le code est unique
        $maxAttempts = 10;
        $attempts = 0;
        while (User::where('promo_code', $promoCode)->exists() && $attempts < $maxAttempts) {
            $suffix = Str::random(4);
            $promoCode = $baseCode . $suffix;
            $attempts++;
        }

        // Si après 10 tentatives on n'a pas trouvé un code unique, utiliser un code complètement aléatoire
        if ($attempts >= $maxAttempts && User::where('promo_code', $promoCode)->exists()) {
            $promoCode = 'PROMO' . strtoupper(Str::random(8));
            // Vérifier une dernière fois
            while (User::where('promo_code', $promoCode)->exists()) {
                $promoCode = 'PROMO' . strtoupper(Str::random(8));
            }
        }

        return $promoCode;
    }
}

