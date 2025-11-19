<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Deposit;

class DepositController extends Controller
{
    /**
     * Store a new deposit.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Validation selon le type de dépôt
        $rules = [
            'deposit_method' => 'required|in:crypto,cash', // Changé de 'method' à 'deposit_method'
            'amount' => 'required|numeric|min:5|max:10000',
        ];

        // Récupérer la méthode de dépôt
        $depositMethod = $request->input('deposit_method');

        if ($depositMethod === 'crypto') {
            $rules['crypto_name'] = 'required|string';
            $rules['transaction_hash'] = 'required|string';
        } elseif ($depositMethod === 'cash') {
            $rules['location'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Création du dépôt
        $deposit = Deposit::create([
            'user_id' => $user->id,
            'method' => $depositMethod,
            'amount' => $request->input('amount'),
            'crypto_name' => $request->input('crypto_name'),
            'transaction_hash' => $request->input('transaction_hash'),
            'location' => $request->input('location'),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deposit submitted successfully!',
            'data' => $deposit
        ], 201);
    }

    /**
     * Get user's deposit history
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $deposits = Deposit::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deposits
        ]);
    }

    /**
     * Get a specific deposit
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $deposit = Deposit::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$deposit) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $deposit
        ]);
    }
}