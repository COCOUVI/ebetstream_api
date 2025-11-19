<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class GameController extends Controller
{
    /**
     * Liste tous les jeux
     */
    public function index(Request $request)
    {
        $query = Game::with('category');

        // Filtrer par catégorie
        if ($request->has('category_id')) {
            $query->where('game_category_id', $request->category_id);
        }

        // Filtrer par nom (recherche partielle)
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filtrer uniquement les actifs
        if ($request->get('active_only', false)) {
            $query->where('is_active', true);
        }

        $games = $query->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($game) {
                return $this->formatGame($game);
            });

        return response()->json([
            'success' => true,
            'data' => $games
        ]);
    }

    /**
     * Récupère un jeu spécifique
     */
    public function show($id)
    {
        $game = Game::with('category')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatGame($game)
        ]);
    }

    /**
     * Crée un nouveau jeu (Admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'game_category_id' => 'required|exists:game_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'position' => 'nullable|integer|min:0',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['game_category_id', 'name', 'description', 'position']);
        $data['is_active'] = $request->get('is_active', true);
        $data['position'] = $data['position'] ?? 0;

        // Gérer l'upload de l'icône
        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('games/icons', 'public');
            $data['icon'] = $path;
        }

        // Gérer l'upload de l'image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('games/images', 'public');
            $data['image'] = $path;
        }

        $game = Game::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Jeu créé avec succès',
            'data' => $this->formatGame($game->load('category'))
        ], 201);
    }

    /**
     * Met à jour un jeu (Admin)
     */
    public function update(Request $request, $id)
    {
        $game = Game::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'game_category_id' => 'sometimes|exists:game_categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['game_category_id', 'name', 'description', 'position', 'is_active']);

        // Gérer l'upload de l'icône
        if ($request->hasFile('icon')) {
            // Supprimer l'ancienne icône si elle existe
            if ($game->icon && Storage::disk('public')->exists($game->icon)) {
                Storage::disk('public')->delete($game->icon);
            }
            
            // Sauvegarder la nouvelle icône
            $path = $request->file('icon')->store('games/icons', 'public');
            $data['icon'] = $path;
        }

        // Gérer l'upload de l'image
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($game->image && Storage::disk('public')->exists($game->image)) {
                Storage::disk('public')->delete($game->image);
            }
            
            // Sauvegarder la nouvelle image
            $path = $request->file('image')->store('games/images', 'public');
            $data['image'] = $path;
        }

        $game->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Jeu mis à jour avec succès',
            'data' => $this->formatGame($game->load('category'))
        ]);
    }

    /**
     * Supprime un jeu (Admin)
     */
    public function destroy($id)
    {
        $game = Game::findOrFail($id);

        // Supprimer les fichiers si ils existent
        if ($game->icon && Storage::disk('public')->exists($game->icon)) {
            Storage::disk('public')->delete($game->icon);
        }
        if ($game->image && Storage::disk('public')->exists($game->image)) {
            Storage::disk('public')->delete($game->image);
        }

        $game->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jeu supprimé avec succès'
        ]);
    }

    /**
     * Formate le jeu avec les URLs complètes
     */
    private function formatGame($game)
    {
        $gameArray = $game->toArray();
        $baseUrl = config('app.url', 'http://127.0.0.1:8000');
        
        // Ajouter l'URL complète de l'icône
        if ($game->icon) {
            $gameArray['icon_url'] = rtrim($baseUrl, '/') . '/storage/' . ltrim($game->icon, '/');
        } else {
            $gameArray['icon_url'] = null;
        }

        // Ajouter l'URL complète de l'image
        if ($game->image) {
            $gameArray['image_url'] = rtrim($baseUrl, '/') . '/storage/' . ltrim($game->image, '/');
        } else {
            $gameArray['image_url'] = null;
        }
        
        return $gameArray;
    }
}
