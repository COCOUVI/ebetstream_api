<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PartnerController extends Controller
{
    /**
     * Récupère les meilleurs développeurs partenaires
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $partners = Partner::where('is_active', true)
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($partner) {
                return $this->formatPartner($partner);
            });

        return response()->json([
            'success' => true,
            'data' => $partners
        ]);
    }

    /**
     * Récupère un partenaire spécifique
     */
    public function show($id)
    {
        $partner = Partner::where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatPartner($partner)
        ]);
    }

    /**
     * Crée un nouveau partenaire (Admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
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

        $data = $request->only(['name', 'specialty', 'website', 'country', 'bio', 'position']);
        $data['is_active'] = $request->get('is_active', true);
        $data['position'] = $data['position'] ?? 0;

        // Gérer l'upload de la photo
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('partners', 'public');
            $data['avatar'] = $path;
            $data['logo'] = $path; // Garder la compatibilité
        } else {
            $data['avatar'] = null;
        }

        $partner = Partner::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Partenaire créé avec succès',
            'data' => $this->formatPartner($partner)
        ], 201);
    }

    /**
     * Met à jour un partenaire (Admin)
     */
    public function update(Request $request, $id)
    {
        $partner = Partner::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
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

        $data = $request->only(['name', 'specialty', 'website', 'country', 'bio', 'position', 'is_active']);

        // Gérer l'upload de la photo
        if ($request->hasFile('avatar')) {
            // Supprimer l'ancienne photo si elle existe
            if ($partner->avatar && Storage::disk('public')->exists($partner->avatar)) {
                Storage::disk('public')->delete($partner->avatar);
            }
            
            // Sauvegarder la nouvelle photo
            $path = $request->file('avatar')->store('partners', 'public');
            $data['avatar'] = $path;
            $data['logo'] = $path; // Garder la compatibilité
        }

        $partner->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Partenaire mis à jour avec succès',
            'data' => $this->formatPartner($partner)
        ]);
    }

    /**
     * Supprime un partenaire (Admin)
     */
    public function destroy($id)
    {
        $partner = Partner::findOrFail($id);

        // Supprimer la photo si elle existe
        if ($partner->avatar && Storage::disk('public')->exists($partner->avatar)) {
            Storage::disk('public')->delete($partner->avatar);
        }

        $partner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partenaire supprimé avec succès'
        ]);
    }

    /**
     * Formate le partenaire avec l'URL complète de l'avatar
     */
    private function formatPartner($partner)
    {
        $partnerArray = $partner->toArray();
        
        // Ajouter l'URL complète de l'avatar
        if ($partner->avatar) {
            $baseUrl = config('app.url', 'http://127.0.0.1:8000');
            $partnerArray['avatar_url'] = rtrim($baseUrl, '/') . '/storage/' . ltrim($partner->avatar, '/');
        } elseif ($partner->logo) {
            $baseUrl = config('app.url', 'http://127.0.0.1:8000');
            $partnerArray['avatar_url'] = rtrim($baseUrl, '/') . '/storage/' . ltrim($partner->logo, '/');
        } else {
            // Photo par défaut
            $partnerArray['avatar_url'] = 'https://ui-avatars.com/api/?name=' . urlencode($partner->name) . '&background=667eea&color=fff&size=200';
        }
        
        return $partnerArray;
    }
}
