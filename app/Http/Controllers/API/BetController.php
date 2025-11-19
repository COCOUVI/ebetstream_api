<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\GameMatch;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BetController extends Controller
{
    /**
     * Liste les paris d'un utilisateur
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Bet::with(['gameMatch.game', 'user'])
            ->where('user_id', $user->id);

        // Filtrer par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bets = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($bet) {
                return $this->formatBet($bet);
            });

        return response()->json([
            'success' => true,
            'data' => $bets
        ]);
    }

    /**
     * Crée un nouveau pari
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'game_match_id' => 'required|exists:game_matches,id',
            'bet_type' => 'required|in:team1_win,draw,team2_win',
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $match = GameMatch::findOrFail($request->game_match_id);

        // Vérifier que le match est disponible pour les paris
        if ($match->status !== 'upcoming' && $match->status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'Ce match n\'est plus disponible pour les paris'
            ], 400);
        }

        // Vérifier le solde de l'utilisateur
        $wallet = Wallet::where('user_id', $user->id)->first();
        if (!$wallet || $wallet->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Solde insuffisant'
            ], 400);
        }

        // Calculer le gain potentiel selon le type de pari
        $odds = match($request->bet_type) {
            'team1_win' => $match->team1_odds,
            'draw' => $match->draw_odds,
            'team2_win' => $match->team2_odds,
        };
        
        $potentialWin = $request->amount * $odds;

        DB::beginTransaction();
        try {
            // Débiter le solde
            $wallet->balance -= $request->amount;
            $wallet->save();

            // Créer le pari
            $bet = Bet::create([
                'user_id' => $user->id,
                'game_match_id' => $match->id,
                'bet_type' => $request->bet_type,
                'amount' => $request->amount,
                'potential_win' => $potentialWin,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pari effectué avec succès',
                'data' => $this->formatBet($bet->load(['gameMatch.game', 'user']))
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du pari: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formate le pari
     */
    private function formatBet($bet)
    {
        return $bet->toArray();
    }
}
