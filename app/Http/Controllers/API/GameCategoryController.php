<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GameCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class GameCategoryController extends Controller
{
    /**
     * Liste toutes les catégories de jeux
     */
    public function index(Request $request)
    {
        $categories = GameCategory::with(['games' => function($query) {
                $query->where('is_active', true)->orderBy('position', 'asc');
            }])
            ->where('is_active', true)
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($category) {
                return $this->formatCategory($category);
            });

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Récupère une catégorie spécifique
     */
    public function show($id)
    {
        $category = GameCategory::with(['games' => function($query) {
                $query->where('is_active', true)->orderBy('position', 'asc');
            }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatCategory($category)
        ]);
    }

    /**
     * Crée une nouvelle catégorie (Admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:game_categories,name',
            'description' => 'nullable|string|max:1000',
            'position' => 'nullable|integer|min:0',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'description', 'position']);
        $data['is_active'] = $request->get('is_active', true);
        $data['position'] = $data['position'] ?? 0;

        // Gérer l'upload de l'icône
        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('game_categories', 'public');
            $data['icon'] = $path;
        }

        $category = GameCategory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => $this->formatCategory($category)
        ], 201);
    }

    /**
     * Met à jour une catégorie (Admin)
     */
    public function update(Request $request, $id)
    {
        $category = GameCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:game_categories,name,' . $id,
            'description' => 'nullable|string|max:1000',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'description', 'position', 'is_active']);

        // Gérer l'upload de l'icône
        if ($request->hasFile('icon')) {
            // Supprimer l'ancienne icône si elle existe
            if ($category->icon && Storage::disk('public')->exists($category->icon)) {
                Storage::disk('public')->delete($category->icon);
            }
            
            // Sauvegarder la nouvelle icône
            $path = $request->file('icon')->store('game_categories', 'public');
            $data['icon'] = $path;
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour avec succès',
            'data' => $this->formatCategory($category)
        ]);
    }

    /**
     * Supprime une catégorie (Admin)
     */
    public function destroy($id)
    {
        $category = GameCategory::findOrFail($id);

        // Supprimer l'icône si elle existe
        if ($category->icon && Storage::disk('public')->exists($category->icon)) {
            Storage::disk('public')->delete($category->icon);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }

    /**
     * Formate la catégorie avec l'URL complète de l'icône et des jeux
     */
    private function formatCategory($category)
    {
        $categoryArray = $category->toArray();
        $baseUrl = config('app.url', 'http://127.0.0.1:8000');
        
        // Ajouter l'URL complète de l'icône
        if ($category->icon) {
            $categoryArray['icon_url'] = rtrim($baseUrl, '/') . '/storage/' . ltrim($category->icon, '/');
        } else {
            $categoryArray['icon_url'] = null;
        }
        
        // Formater les jeux si présents
        if (isset($categoryArray['games']) && is_array($categoryArray['games'])) {
            $categoryArray['games'] = array_map(function($game) use ($baseUrl) {
                if (isset($game['icon']) && $game['icon']) {
                    $game['icon_url'] = rtrim($baseUrl, '/') . '/storage/' . ltrim($game['icon'], '/');
                } else {
                    $game['icon_url'] = null;
                }
                if (isset($game['image']) && $game['image']) {
                    $game['image_url'] = rtrim($baseUrl, '/') . '/storage/' . ltrim($game['image'], '/');
                } else {
                    $game['image_url'] = null;
                }
                return $game;
            }, $categoryArray['games']);
        }
        
        return $categoryArray;
    }
}
