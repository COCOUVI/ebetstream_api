<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CertificationRequest;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CertificationController extends Controller
{
    /**
     * Vérifier les conditions d'éligibilité pour la certification
     */
    public function checkEligibility(Request $request)
    {
        $user = $request->user();
        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil non trouvé'
            ], 404);
        }

        $conditions = $this->verifyConditions($user, $profile);

        return response()->json([
            'success' => true,
            'eligible' => $conditions['all_met'],
            'conditions' => $conditions
        ]);
    }

    /**
     * Soumettre une demande de certification
     */
    public function submitRequest(Request $request)
    {
        $user = $request->user();
        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil non trouvé'
            ], 404);
        }

        // Vérifier les conditions
        $conditions = $this->verifyConditions($user, $profile);
        
        if (!$conditions['all_met']) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne remplissez pas toutes les conditions requises pour la certification',
                'conditions' => $conditions
            ], 422);
        }

        // Vérifier si une demande est déjà en cours
        $existingRequest = CertificationRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà une demande de certification en cours ou approuvée',
                'request' => $existingRequest
            ], 422);
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'date_of_birth' => 'required|date|before:today',
            'id_type' => 'required|in:passport,national_id,driving_license,residence_permit',
            'id_number' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier l'âge (18 ans minimum)
        $dateOfBirth = Carbon::parse($request->date_of_birth);
        $age = $dateOfBirth->age;

        if ($age < 18) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez avoir au moins 18 ans pour demander la certification'
            ], 422);
        }

        // Créer la demande
        $certificationRequest = CertificationRequest::create([
            'user_id' => $user->id,
            'date_of_birth' => $request->date_of_birth,
            'id_type' => $request->id_type,
            'id_number' => $request->id_number,
            'status' => 'pending',
            'verification_data' => $conditions
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de certification soumise avec succès',
            'data' => $certificationRequest->load('user')
        ]);
    }

    /**
     * Obtenir le statut de la demande de certification de l'utilisateur
     */
    public function getStatus(Request $request)
    {
        $user = $request->user();
        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil non trouvé'
            ], 404);
        }

        $certificationRequest = CertificationRequest::where('user_id', $user->id)
            ->latest()
            ->first();

        $conditions = $this->verifyConditions($user, $profile);

        return response()->json([
            'success' => true,
            'request' => $certificationRequest,
            'conditions' => $conditions,
            'eligible' => $conditions['all_met']
        ]);
    }

    /**
     * Vérifier toutes les conditions pour la certification
     */
    private function verifyConditions($user, $profile)
    {
        $conditions = [
            'age_requirement' => [
                'met' => false,
                'message' => 'Avoir plus de 18 ans (ou majorité légale du pays)',
                'details' => 'Vous devez fournir votre date de naissance lors de la demande'
            ],
            'complete_profile' => [
                'met' => false,
                'message' => 'Posséder un profil complet',
                'details' => []
            ],
            'good_behavior' => [
                'met' => true, // Par défaut, on assume un bon comportement
                'message' => 'Avoir un comportement exemplaire',
                'details' => 'Zéro sanction, zéro fraude, zéro comportement toxique'
            ],
            'positive_reputation' => [
                'met' => true, // Par défaut, on assume une bonne réputation
                'message' => 'Avoir une réputation positive',
                'details' => 'Score de fair-play, avis positifs, historique propre'
            ]
        ];

        // Vérifier le profil complet
        $profileComplete = true;
        $missingFields = [];

        if (empty($profile->profile_photo) && empty($profile->avatar)) {
            $profileComplete = false;
            $missingFields[] = 'Photo de profil';
        }
        if (empty($profile->bio)) {
            $profileComplete = false;
            $missingFields[] = 'Biographie';
        }
        if (empty($profile->country)) {
            $profileComplete = false;
            $missingFields[] = 'Pays';
        }
        if (empty($profile->full_name)) {
            $profileComplete = false;
            $missingFields[] = 'Nom complet';
        }

        $conditions['complete_profile']['met'] = $profileComplete;
        if (!$profileComplete) {
            $conditions['complete_profile']['details'] = 'Champs manquants: ' . implode(', ', $missingFields);
        }

        // Vérifier si toutes les conditions sont remplies
        $allMet = $conditions['complete_profile']['met'] 
               && $conditions['good_behavior']['met'] 
               && $conditions['positive_reputation']['met'];

        $conditions['all_met'] = $allMet;

        return $conditions;
    }
}
