<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Withdrawal;
use App\Models\Wallet;

class WithdrawalController extends Controller
{
    /**
     * Store a new withdrawal request.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Get user's wallet
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found'
            ], 404);
        }

        // Validation rules
        $rules = [
            'withdrawal_method' => 'required|in:crypto,bank_transfer,mobile_money',
            'amount' => 'required|numeric|min:10|max:50000',
        ];

        $withdrawalMethod = $request->input('withdrawal_method');

        // Method-specific validation
        if ($withdrawalMethod === 'crypto') {
            $rules['crypto_name'] = 'required|string';
            $rules['crypto_address'] = 'required|string|min:10';
        } elseif ($withdrawalMethod === 'bank_transfer') {
            $rules['bank_name'] = 'required|string';
            $rules['account_number'] = 'required|string';
            $rules['account_holder_name'] = 'required|string';
        } elseif ($withdrawalMethod === 'mobile_money') {
            $rules['mobile_money_provider'] = 'required|string';
            $rules['mobile_money_number'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $amount = $request->input('amount');

        // Check if user has sufficient balance
        if ($wallet->balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance'
            ], 400);
        }

        // Create withdrawal request
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'method' => $withdrawalMethod,
            'amount' => $amount,
            'crypto_name' => $request->input('crypto_name'),
            'crypto_address' => $request->input('crypto_address'),
            'bank_name' => $request->input('bank_name'),
            'account_number' => $request->input('account_number'),
            'account_holder_name' => $request->input('account_holder_name'),
            'mobile_money_provider' => $request->input('mobile_money_provider'),
            'mobile_money_number' => $request->input('mobile_money_number'),
            'status' => 'pending',
        ]);

        // Lock the amount in wallet (optional - depends on your business logic)
        // $wallet->locked_balance += $amount;
        // $wallet->balance -= $amount;
        // $wallet->save();

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request submitted successfully!',
            'data' => $withdrawal
        ], 201);
    }

    /**
     * Get user's withdrawal history
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $withdrawals = Withdrawal::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    /**
     * Get specific withdrawal
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $withdrawal = Withdrawal::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$withdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $withdrawal
        ]);
    }
}




