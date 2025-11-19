<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Models\Challenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TopPlayersController extends Controller
{
    /**
     * Récupère les meilleurs joueurs de la semaine
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 10);
        $period = $request->get('period', 'week'); // week, month, all

        // Calculer la date de début selon la période
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'all' => null,
            default => now()->startOfWeek(),
        };

        // Récupérer tous les utilisateurs avec leur profil
        $users = User::with('profile')
            ->whereHas('profile')
            ->get();

        $players = [];

        foreach ($users as $user) {
            $profile = $user->profile;
            
            if (!$profile) {
                continue; // Skip users without profile
            }
            
            // Calculer le score du joueur
            $score = $this->calculatePlayerScore($user->id, $profile, $startDate);
            
            // Récupérer les statistiques
            $stats = $this->getPlayerStats($user->id, $startDate);
            
            // Photo de profil
            $avatarUrl = null;
            if ($profile->profile_photo) {
                $baseUrl = config('app.url', 'http://127.0.0.1:8000');
                $avatarUrl = rtrim($baseUrl, '/') . '/storage/' . ltrim($profile->profile_photo, '/');
            } elseif ($profile->avatar) {
                $baseUrl = config('app.url', 'http://127.0.0.1:8000');
                $avatarUrl = rtrim($baseUrl, '/') . '/storage/' . ltrim($profile->avatar, '/');
            } else {
                $name = $profile->full_name ?? $user->username;
                $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=667eea&color=fff&size=200';
            }

            $players[] = [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $profile->full_name ?? $user->username,
                'avatar_url' => $avatarUrl,
                'score' => $score,
                'wins' => $profile->wins ?? 0,
                'losses' => $profile->losses ?? 0,
                'ratio' => $profile->ratio ?? 0,
                'country' => $profile->country,
                'bio' => $profile->bio,
                'global_score' => $profile->global_score ?? 0,
                'stats' => $stats,
            ];
        }

        // Trier par score décroissant
        usort($players, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Limiter les résultats
        $players = array_slice($players, 0, $limit);

        return response()->json([
            'success' => true,
            'data' => $players
        ]);
    }

    /**
     * Récupère un joueur spécifique
     */
    public function show($id)
    {
        $user = User::with('profile')->findOrFail($id);
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil non trouvé'
            ], 404);
        }

        // Calculer le score
        $score = $this->calculatePlayerScore($user->id, $profile, now()->startOfWeek());
        
        // Statistiques
        $stats = $this->getPlayerStats($user->id, now()->startOfWeek());
        
        // Photo de profil
        $avatarUrl = null;
        if ($profile->profile_photo) {
            $baseUrl = config('app.url', 'http://127.0.0.1:8000');
            $avatarUrl = rtrim($baseUrl, '/') . '/storage/' . ltrim($profile->profile_photo, '/');
        } elseif ($profile->avatar) {
            $baseUrl = config('app.url', 'http://127.0.0.1:8000');
            $avatarUrl = rtrim($baseUrl, '/') . '/storage/' . ltrim($profile->avatar, '/');
        } else {
            $name = $profile->full_name ?? $user->username;
            $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=667eea&color=fff&size=200';
        }

        // Calculer le rang
        $rank = $this->getPlayerRank($user->id);

        $player = [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $profile->full_name ?? $user->username,
            'avatar_url' => $avatarUrl,
            'score' => $score,
            'rank' => $rank,
            'wins' => $profile->wins ?? 0,
            'losses' => $profile->losses ?? 0,
            'ratio' => $profile->ratio ?? 0,
            'country' => $profile->country,
            'bio' => $profile->bio,
            'global_score' => $profile->global_score ?? 0,
            'tournaments_won' => $profile->tournaments_won ?? 0,
            'stats' => $stats,
        ];

        return response()->json([
            'success' => true,
            'data' => $player
        ]);
    }

    /**
     * Calcule le score d'un joueur
     */
    private function calculatePlayerScore($userId, $profile, $startDate = null)
    {
        $score = 0;

        // Score de base : global_score
        $score += $profile->global_score ?? 0;

        // Points pour les victoires (10 points par victoire)
        $score += ($profile->wins ?? 0) * 10;

        // Points pour les défis gagnés cette semaine
        if ($startDate) {
            $challengesWon = Challenge::where(function($q) use ($userId) {
                    $q->where('creator_id', $userId)
                      ->orWhere('opponent_id', $userId);
                })
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->where(function($q) use ($userId) {
                    $q->where(function($q2) use ($userId) {
                        $q2->where('creator_id', $userId)
                           ->whereColumn('creator_score', '>', 'opponent_score');
                    })->orWhere(function($q2) use ($userId) {
                        $q2->where('opponent_id', $userId)
                           ->whereColumn('opponent_score', '>', 'creator_score');
                    });
                })
                ->count();
            
            $score += $challengesWon * 50; // 50 points par défi gagné cette semaine
        }

        // Bonus pour le ratio de victoires (si ratio > 0.5)
        if (($profile->ratio ?? 0) > 0.5) {
            $score += (($profile->ratio ?? 0) - 0.5) * 100;
        }

        // Bonus pour les tournois gagnés
        $score += ($profile->tournaments_won ?? 0) * 100;

        return (int) $score;
    }

    /**
     * Récupère les statistiques d'un joueur
     */
    private function getPlayerStats($userId, $startDate = null)
    {
        $query = Challenge::where(function($q) use ($userId) {
            $q->where('creator_id', $userId)
              ->orWhere('opponent_id', $userId);
        });

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        $totalChallenges = $query->count();
        
        $wonChallenges = Challenge::where(function($q) use ($userId) {
                $q->where('creator_id', $userId)
                  ->orWhere('opponent_id', $userId);
            })
            ->where('status', 'completed')
            ->where(function($q) use ($userId) {
                $q->where(function($q2) use ($userId) {
                    $q2->where('creator_id', $userId)
                       ->whereColumn('creator_score', '>', 'opponent_score');
                })->orWhere(function($q2) use ($userId) {
                    $q2->where('opponent_id', $userId)
                       ->whereColumn('opponent_score', '>', 'creator_score');
                });
            });

        if ($startDate) {
            $wonChallenges->where('created_at', '>=', $startDate);
        }

        $wonCount = $wonChallenges->count();

        return [
            'total_challenges' => $totalChallenges,
            'won_challenges' => $wonCount,
            'lost_challenges' => $totalChallenges - $wonCount,
        ];
    }

    /**
     * Récupère le rang d'un joueur
     */
    private function getPlayerRank($userId)
    {
        $users = User::with('profile')
            ->whereHas('profile')
            ->get();

        $players = [];
        foreach ($users as $user) {
            $profile = $user->profile;
            $score = $this->calculatePlayerScore($user->id, $profile, now()->startOfWeek());
            $players[] = ['id' => $user->id, 'score' => $score];
        }

        usort($players, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $rank = 1;
        foreach ($players as $player) {
            if ($player['id'] == $userId) {
                return $rank;
            }
            $rank++;
        }

        return $rank;
    }
}
