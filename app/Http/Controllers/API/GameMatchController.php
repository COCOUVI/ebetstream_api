<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GameMatchController extends Controller
{
    /**
     * Liste les matches d'un jeu
     */
    public function index(Request $request)
    {
        $query = GameMatch::with('game');

        // Filtrer par jeu
        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        // Filtrer par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer uniquement les actifs
        $query->where('is_active', true);

        // Trier par date de match
        $matches = $query->orderBy('match_date', 'asc')
            ->get()
            ->map(function ($match) {
                return $this->formatMatch($match);
            });

        return response()->json([
            'success' => true,
            'data' => $matches
        ]);
    }

    /**
     * Récupère un match spécifique
     */
    public function show($id)
    {
        $match = GameMatch::with('game', 'bets')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatMatch($match)
        ]);
    }

    /**
     * Crée un nouveau match (Admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'game_id' => 'required|exists:games,id',
            'team1_name' => 'required|string|max:255',
            'team2_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'match_date' => 'required|date',
            'team1_odds' => 'nullable|numeric|min:0.01',
            'draw_odds' => 'nullable|numeric|min:0.01',
            'team2_odds' => 'nullable|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'game_id', 'team1_name', 'team2_name', 'description', 
            'match_date', 'team1_odds', 'draw_odds', 'team2_odds'
        ]);
        
        // Valeurs par défaut pour les cotes
        $data['team1_odds'] = $data['team1_odds'] ?? 1.00;
        $data['draw_odds'] = $data['draw_odds'] ?? 0.50;
        $data['team2_odds'] = $data['team2_odds'] ?? 1.00;
        $data['status'] = 'upcoming';
        $data['is_active'] = true;

        $match = GameMatch::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Match créé avec succès',
            'data' => $this->formatMatch($match->load('game'))
        ], 201);
    }

    /**
     * Met à jour un match (Admin)
     */
    public function update(Request $request, $id)
    {
        $match = GameMatch::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'team1_name' => 'sometimes|string|max:255',
            'team2_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'match_date' => 'sometimes|date',
            'status' => 'sometimes|in:upcoming,live,finished,cancelled',
            'result' => 'nullable|in:team1_win,draw,team2_win',
            'team1_odds' => 'nullable|numeric|min:0.01',
            'draw_odds' => 'nullable|numeric|min:0.01',
            'team2_odds' => 'nullable|numeric|min:0.01',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $match->update($request->only([
            'team1_name', 'team2_name', 'description', 'match_date',
            'status', 'result', 'team1_odds', 'draw_odds', 'team2_odds', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Match mis à jour avec succès',
            'data' => $this->formatMatch($match->load('game'))
        ]);
    }

    /**
     * Supprime un match (Admin)
     */
    public function destroy($id)
    {
        $match = GameMatch::findOrFail($id);
        $match->delete();

        return response()->json([
            'success' => true,
            'message' => 'Match supprimé avec succès'
        ]);
    }

    /**
     * Formate le match
     */
    private function formatMatch($match)
    {
        return $match->toArray();
    }
}
