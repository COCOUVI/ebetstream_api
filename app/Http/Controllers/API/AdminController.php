<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Challenge;
use App\Models\Stream;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\Ambassador;
use App\Models\Partner;
use App\Models\CertificationRequest;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Récupère les statistiques générales
     */
    public function stats(Request $request)
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalChallenges' => Challenge::count(),
            'totalStreams' => Stream::count(),
            'totalDeposits' => Deposit::where('status', 'approved')->sum('amount'),
            'totalWithdrawals' => Withdrawal::where('status', 'approved')->sum('amount'),
            'totalAmbassadors' => Ambassador::where('is_active', true)->count(),
            'totalPartners' => Partner::where('is_active', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Liste tous les utilisateurs
     */
    public function users(Request $request)
    {
        $users = User::with(['wallet', 'profile'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        // Ajouter le solde de chaque utilisateur
        $users->getCollection()->transform(function ($user) {
            $user->balance = $user->wallet ? $user->wallet->balance : 0;
            $user->role = $user->role ?? 'player';
            return $user;
        });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'nullable|string|max:255|unique:users,username',
            'password' => 'required|string|min:8',
            'role' => 'nullable|string|in:user,admin',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        // Créer le profil si un nom est fourni
        if (isset($validated['name'])) {
            $user->profile()->create([
                'full_name' => $validated['name'],
            ]);
        }

        // Créer le wallet (si n'existe pas déjà)
        $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'locked_balance' => 0,
                'currency' => 'USD',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'data' => $user->load(['wallet', 'profile'])
        ], 201);
    }

    /**
     * Met à jour un utilisateur
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'username' => 'nullable|string|max:255|unique:users,username,' . $id,
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|string|in:user,admin',
        ]);

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['username'])) {
            $user->username = $validated['username'];
        }
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        // Mettre à jour le profil
        if (isset($validated['name'])) {
            $profile = $user->profile;
            if ($profile) {
                $profile->full_name = $validated['name'];
                $profile->save();
            } else {
                $user->profile()->create([
                    'full_name' => $validated['name'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => $user->load(['wallet', 'profile'])
        ]);
    }

    /**
     * Supprime un utilisateur
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }

    /**
     * Liste tous les dépôts
     */
    public function deposits(Request $request)
    {
        $deposits = Deposit::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $deposits
        ]);
    }

    /**
     * Approuve un dépôt
     */
    public function approveDeposit(Request $request, $id)
    {
        $deposit = Deposit::findOrFail($id);
        
        if ($deposit->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ce dépôt ne peut pas être approuvé'
            ], 400);
        }

        $deposit->status = 'approved';
        $deposit->save();

        // Ajouter le montant au wallet de l'utilisateur
        $wallet = $deposit->user->wallet;
        if ($wallet) {
            $wallet->balance += $deposit->amount;
            $wallet->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Dépôt approuvé avec succès'
        ]);
    }

    /**
     * Rejette un dépôt
     */
    public function rejectDeposit(Request $request, $id)
    {
        $deposit = Deposit::findOrFail($id);
        
        if ($deposit->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ce dépôt ne peut pas être rejeté'
            ], 400);
        }

        $deposit->status = 'rejected';
        $deposit->save();

        return response()->json([
            'success' => true,
            'message' => 'Dépôt rejeté'
        ]);
    }

    /**
     * Liste tous les retraits
     */
    public function withdrawals(Request $request)
    {
        $withdrawals = Withdrawal::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    /**
     * Approuve un retrait
     */
    public function approveWithdrawal(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        
        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ce retrait ne peut pas être approuvé'
            ], 400);
        }

        $withdrawal->status = 'approved';
        $withdrawal->save();

        // Le montant est déjà déduit du wallet lors de la création du retrait

        return response()->json([
            'success' => true,
            'message' => 'Retrait approuvé avec succès'
        ]);
    }

    /**
     * Rejette un retrait
     */
    public function rejectWithdrawal(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        
        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ce retrait ne peut pas être rejeté'
            ], 400);
        }

        // Remettre le montant dans le wallet
        $wallet = $withdrawal->user->wallet;
        if ($wallet) {
            $wallet->balance += $withdrawal->amount;
            $wallet->locked_balance -= $withdrawal->amount;
            $wallet->save();
        }

        $withdrawal->status = 'rejected';
        $withdrawal->save();

        return response()->json([
            'success' => true,
            'message' => 'Retrait rejeté'
        ]);
    }

    /**
     * Liste toutes les demandes de certification
     */
    public function certifications(Request $request)
    {
        $status = $request->get('status');
        $query = CertificationRequest::with(['user.profile', 'reviewer']);

        if ($status) {
            $query->where('status', $status);
        }

        $certifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $certifications
        ]);
    }

    /**
     * Approuve une demande de certification
     */
    public function approveCertification(Request $request, $id)
    {
        $certificationRequest = CertificationRequest::with('user.profile')->findOrFail($id);
        
        if ($certificationRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cette demande ne peut pas être approuvée'
            ], 400);
        }

        $admin = $request->user();
        
        $certificationRequest->status = 'approved';
        $certificationRequest->reviewed_by = $admin->id;
        $certificationRequest->reviewed_at = now();
        $certificationRequest->save();

        // Ajouter la certification au profil de l'utilisateur
        $profile = $certificationRequest->user->profile;
        if ($profile) {
            $certifications = $profile->certifications ?? [];
            if (!in_array('Ebetstream', $certifications)) {
                $certifications[] = 'Ebetstream';
                $profile->certifications = $certifications;
                $profile->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Certification approuvée avec succès',
            'data' => $certificationRequest->load(['user.profile', 'reviewer'])
        ]);
    }

    /**
     * Rejette une demande de certification
     */
    public function rejectCertification(Request $request, $id)
    {
        $certificationRequest = CertificationRequest::findOrFail($id);
        
        if ($certificationRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cette demande ne peut pas être rejetée'
            ], 400);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $admin = $request->user();
        
        $certificationRequest->status = 'rejected';
        $certificationRequest->reviewed_by = $admin->id;
        $certificationRequest->reviewed_at = now();
        $certificationRequest->rejection_reason = $validated['rejection_reason'];
        $certificationRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Certification rejetée',
            'data' => $certificationRequest->load(['user.profile', 'reviewer'])
        ]);
    }

    /**
     * Obtient les détails d'une demande de certification
     */
    public function getCertification($id)
    {
        $certificationRequest = CertificationRequest::with(['user.profile', 'reviewer'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $certificationRequest
        ]);
    }
}
