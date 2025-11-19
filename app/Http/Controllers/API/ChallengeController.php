<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ChallengeController extends Controller
{
    /**
     * Get all challenges (with filters)
     */
    public function index(Request $request)
    {
        $query = Challenge::with(['creator', 'opponent']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter open challenges only
        if ($request->has('open_only') && $request->open_only) {
            $query->open();
        }

        // Filter by game
        if ($request->has('game')) {
            $query->where('game', 'like', '%' . $request->game . '%');
        }

        // Get user's challenges
        if ($request->has('my_challenges') && $request->my_challenges) {
            $query->forUser($request->user()->id);
        }

        $challenges = $query->orderBy('created_at', 'desc')->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $challenges
        ]);
    }

    /**
     * Get a specific challenge
     */
    public function show($id)
    {
        $challenge = Challenge::with(['creator', 'opponent'])->find($id);

        if (!$challenge) {
            return response()->json([
                'success' => false,
                'message' => 'Challenge not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $challenge
        ]);
    }

    /**
     * Create a new challenge
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'game' => 'required|string|max:255',
            'bet_amount' => 'required|numeric|min:10|max:10000',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user has sufficient balance
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found'
            ], 404);
        }

        $betAmount = $request->bet_amount;
        $availableBalance = $wallet->balance - $wallet->locked_balance;

        if ($availableBalance < $betAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Lock the bet amount
            $wallet->locked_balance += $betAmount;
            $wallet->save();

            // Create challenge
            $challenge = Challenge::create([
                'creator_id' => $user->id,
                'game' => $request->game,
                'bet_amount' => $betAmount,
                'status' => 'open',
                'expires_at' => $request->expires_at ?? now()->addDays(7),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Challenge created successfully',
                'data' => $challenge->load(['creator', 'opponent'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating challenge: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept a challenge
     */
    public function accept(Request $request, $id)
    {
        $user = $request->user();
        $challenge = Challenge::find($id);

        if (!$challenge) {
            return response()->json([
                'success' => false,
                'message' => 'Challenge not found'
            ], 404);
        }

        if ($challenge->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Challenge is not open'
            ], 400);
        }

        if ($challenge->creator_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot accept your own challenge'
            ], 400);
        }

        // Check if challenge has expired
        if ($challenge->expires_at && $challenge->expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Challenge has expired'
            ], 400);
        }

        // Check if user has sufficient balance
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found'
            ], 404);
        }

        $availableBalance = $wallet->balance - $wallet->locked_balance;

        if ($availableBalance < $challenge->bet_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Lock the bet amount for opponent
            $wallet->locked_balance += $challenge->bet_amount;
            $wallet->save();

            // Update challenge
            $challenge->opponent_id = $user->id;
            $challenge->status = 'accepted';
            $challenge->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Challenge accepted successfully',
                'data' => $challenge->load(['creator', 'opponent'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error accepting challenge: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a challenge
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $challenge = Challenge::find($id);

        if (!$challenge) {
            return response()->json([
                'success' => false,
                'message' => 'Challenge not found'
            ], 404);
        }

        // Only creator can cancel, and only if challenge is open
        if ($challenge->creator_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the creator can cancel this challenge'
            ], 403);
        }

        if ($challenge->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Challenge cannot be cancelled'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Unlock the bet amount
            $wallet = Wallet::where('user_id', $user->id)->first();
            if ($wallet) {
                $wallet->locked_balance -= $challenge->bet_amount;
                $wallet->save();
            }

            // Update challenge status
            $challenge->status = 'cancelled';
            $challenge->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Challenge cancelled successfully',
                'data' => $challenge
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling challenge: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit scores for a challenge
     */
    public function submitScores(Request $request, $id)
    {
        $user = $request->user();
        $challenge = Challenge::find($id);

        if (!$challenge) {
            return response()->json([
                'success' => false,
                'message' => 'Challenge not found'
            ], 404);
        }

        if ($challenge->status !== 'accepted' && $challenge->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Challenge is not in a valid state for score submission'
            ], 400);
        }

        // Check if user is part of the challenge
        if ($challenge->creator_id !== $user->id && $challenge->opponent_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not part of this challenge'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'score' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update score based on user role
            if ($challenge->creator_id === $user->id) {
                $challenge->creator_score = $request->score;
            } else {
                $challenge->opponent_score = $request->score;
            }

            // If both scores are set, determine winner and complete challenge
            if ($challenge->creator_score !== null && $challenge->opponent_score !== null) {
                $challenge->status = 'completed';
                
                // Determine winner and distribute winnings
                $this->distributeWinnings($challenge);
            } else {
                $challenge->status = 'in_progress';
            }

            $challenge->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Score submitted successfully',
                'data' => $challenge->load(['creator', 'opponent'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error submitting score: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Distribute winnings after challenge completion
     */
    private function distributeWinnings(Challenge $challenge)
    {
        $totalPot = $challenge->bet_amount * 2; // Both players bet the same amount
        
        $creatorWallet = Wallet::where('user_id', $challenge->creator_id)->first();
        $opponentWallet = Wallet::where('user_id', $challenge->opponent_id)->first();

        if ($challenge->creator_score > $challenge->opponent_score) {
            // Creator wins
            $creatorWallet->balance += $totalPot;
            $creatorWallet->locked_balance -= $challenge->bet_amount;
            $opponentWallet->locked_balance -= $challenge->bet_amount;
        } elseif ($challenge->opponent_score > $challenge->creator_score) {
            // Opponent wins
            $opponentWallet->balance += $totalPot;
            $creatorWallet->locked_balance -= $challenge->bet_amount;
            $opponentWallet->locked_balance -= $challenge->bet_amount;
        } else {
            // Draw - refund both players
            $creatorWallet->balance += $challenge->bet_amount;
            $opponentWallet->balance += $challenge->bet_amount;
            $creatorWallet->locked_balance -= $challenge->bet_amount;
            $opponentWallet->locked_balance -= $challenge->bet_amount;
        }

        $creatorWallet->save();
        $opponentWallet->save();
    }
}
