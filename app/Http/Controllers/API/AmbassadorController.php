<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ambassador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AmbassadorController extends Controller
{
    /**
     * Récupère les 10 meilleurs ambassadeurs de la semaine
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $ambassadors = Ambassador::where('is_active', true)
            ->orderBy('score', 'desc')
            ->orderBy('position', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($ambassador) {
                return $this->formatAmbassador($ambassador);
            });

        return response()->json([
            'success' => true,
            'data' => $ambassadors
        ]);
    }

    /**
     * Récupère un ambassadeur spécifique
     */
    public function show($id)
    {
        $ambassador = Ambassador::where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatAmbassador($ambassador)
        ]);
    }

    /**
     * Crée un nouvel ambassadeur (Admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:ambassadors,username',
            'score' => 'nullable|integer|min:0',
            'country' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'position' => 'nullable|integer|min:0',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'username', 'score', 'country', 'bio', 'position']);
        $data['is_active'] = $request->get('is_active', true);
        $data['score'] = $data['score'] ?? 0;
        $data['position'] = $data['position'] ?? 0;

        // Gérer l'upload de la photo
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('ambassadors', 'public');
            $data['avatar'] = $path;
        } else {
            // Photo par défaut
            $data['avatar'] = null;
        }

        $ambassador = Ambassador::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Ambassadeur créé avec succès',
            'data' => $this->formatAmbassador($ambassador)
        ], 201);
    }

    /**
     * Met à jour un ambassadeur (Admin)
     */
    public function update(Request $request, $id)
    {
        $ambassador = Ambassador::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:ambassadors,username,' . $id,
            'score' => 'sometimes|integer|min:0',
            'country' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'username', 'score', 'country', 'bio', 'position', 'is_active']);

        // Gérer l'upload de la photo
        if ($request->hasFile('avatar')) {
            // Supprimer l'ancienne photo si elle existe
            if ($ambassador->avatar && Storage::disk('public')->exists($ambassador->avatar)) {
                Storage::disk('public')->delete($ambassador->avatar);
            }
            
            // Sauvegarder la nouvelle photo
            $path = $request->file('avatar')->store('ambassadors', 'public');
            $data['avatar'] = $path;
        }

        $ambassador->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Ambassadeur mis à jour avec succès',
            'data' => $this->formatAmbassador($ambassador)
        ]);
    }

    /**
     * Supprime un ambassadeur (Admin)
     */
    public function destroy($id)
    {
        $ambassador = Ambassador::findOrFail($id);

        // Supprimer la photo si elle existe
        if ($ambassador->avatar && Storage::disk('public')->exists($ambassador->avatar)) {
            Storage::disk('public')->delete($ambassador->avatar);
        }

        $ambassador->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ambassadeur supprimé avec succès'
        ]);
    }

    /**
     * Formate l'ambassadeur avec l'URL complète de l'avatar
     */
    private function formatAmbassador($ambassador)
    {
        $ambassadorArray = $ambassador->toArray();
        
        // Ajouter l'URL complète de l'avatar
        if ($ambassador->avatar) {
            $ambassadorArray['avatar_url'] = Storage::disk('public')->url($ambassador->avatar);
        } else {
            // Photo par défaut - utiliser une URL de placeholder ou une image SVG encodée
            $ambassadorArray['avatar_url'] = 'https://ui-avatars.com/api/?name=' . urlencode($ambassador->name) . '&background=667eea&color=fff&size=200';
        }
        
        return $ambassadorArray;
    }
}
