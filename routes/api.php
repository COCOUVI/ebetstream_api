<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\RechargeAgentController;
use App\Http\Controllers\API\ChallengeController;
use App\Http\Controllers\API\StreamController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\AmbassadorController;
use App\Http\Controllers\API\TopPlayersController;
use App\Http\Controllers\API\PartnerController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\GameCategoryController;
use App\Http\Controllers\API\GameController;
use App\Http\Controllers\API\GameMatchController;
use App\Http\Controllers\API\BetController;
use App\Http\Controllers\API\CertificationController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\WithdrawalController;

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!'
    ]);
});

// Register
Route::post('/register', [RegisterController::class, 'register']);

// Login (rate limited)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Public routes
Route::get('/recharge-agents', [RechargeAgentController::class, 'index']);

// Public ambassador routes
Route::get('/ambassadors', [AmbassadorController::class, 'index']);
Route::get('/ambassadors/{id}', [AmbassadorController::class, 'show']);

// Public top players routes
Route::get('/top-players', [TopPlayersController::class, 'index']);
Route::get('/top-players/{id}', [TopPlayersController::class, 'show']);

// Public partner routes
Route::get('/partners', [PartnerController::class, 'index']);
Route::get('/partners/{id}', [PartnerController::class, 'show']);

// Public game routes
Route::get('/game-categories', [GameCategoryController::class, 'index']);
Route::get('/game-categories/{id}', [GameCategoryController::class, 'show']);
Route::get('/games', [GameController::class, 'index']);
Route::get('/games/{id}', [GameController::class, 'show']);

// Public game matches routes
Route::get('/game-matches', [GameMatchController::class, 'index']);
Route::get('/game-matches/{id}', [GameMatchController::class, 'show']);

// Public stream routes (viewing streams)
Route::get('/streams', [StreamController::class, 'index']);
Route::get('/streams/{id}', [StreamController::class, 'show']);
Route::get('/streams/{id}/chat', [StreamController::class, 'getChatMessages']);

// Protected routes (user must be logged in)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Deposit routes
    Route::prefix('deposits')->group(function () {
        Route::post('/', [DepositController::class, 'store']);      // Create deposit
        Route::get('/', [DepositController::class, 'index']);        // List user deposits
        Route::get('/{id}', [DepositController::class, 'show']);     // Get specific deposit
    });

    // Withdrawal routes
    Route::prefix('withdrawals')->group(function () {
        Route::post('/', [WithdrawalController::class, 'store']);      // Create withdrawal
        Route::get('/', [WithdrawalController::class, 'index']);        // List user withdrawals
        Route::get('/{id}', [WithdrawalController::class, 'show']);     // Get specific withdrawal
    });

    // Challenge routes
    Route::prefix('challenges')->group(function () {
        Route::get('/', [ChallengeController::class, 'index']);              // List challenges
        Route::post('/', [ChallengeController::class, 'store']);            // Create challenge
        Route::get('/{id}', [ChallengeController::class, 'show']);           // Get challenge
        Route::post('/{id}/accept', [ChallengeController::class, 'accept']); // Accept challenge
        Route::post('/{id}/cancel', [ChallengeController::class, 'cancel']); // Cancel challenge
        Route::post('/{id}/scores', [ChallengeController::class, 'submitScores']); // Submit scores
    });

    // Stream routes
    Route::prefix('streams')->group(function () {
        Route::get('/', [StreamController::class, 'index']);                    // List streams
        Route::post('/', [StreamController::class, 'store']);                    // Create stream
        Route::get('/{id}', [StreamController::class, 'show']);                 // Get stream
        Route::put('/{id}', [StreamController::class, 'update']);               // Update stream
        Route::post('/{id}/start', [StreamController::class, 'start']);         // Start stream
        Route::post('/{id}/stop', [StreamController::class, 'stop']);           // Stop stream
        Route::get('/{id}/chat', [StreamController::class, 'getChatMessages']); // Get chat messages
        Route::post('/{id}/chat', [StreamController::class, 'sendChatMessage']); // Send chat message
        Route::delete('/{id}/chat/{messageId}', [StreamController::class, 'deleteChatMessage']); // Delete chat message
        Route::post('/{id}/follow', [StreamController::class, 'toggleFollow']); // Follow/Unfollow
        Route::post('/{id}/viewers', [StreamController::class, 'updateViewers']); // Update viewers
    });
    
    // Stream key route (for streamer)
    Route::get('/stream-key', [StreamController::class, 'getStreamKey']);

    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']); // Get profile
        Route::post('/', [ProfileController::class, 'update']); // Update profile (with file upload)
        Route::put('/', [ProfileController::class, 'update']); // Update profile
        Route::get('/qr-code', [ProfileController::class, 'getQRCode']); // Get QR code
    });

    // Certification routes
    Route::prefix('certification')->group(function () {
        Route::get('/eligibility', [CertificationController::class, 'checkEligibility']); // Check eligibility
        Route::get('/status', [CertificationController::class, 'getStatus']); // Get certification status
        Route::post('/request', [CertificationController::class, 'submitRequest']); // Submit certification request
    });

    // Wallet route
    Route::get('/wallet', function (Request $request) {
        $user = $request->user();
        $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
        
        // Create wallet if doesn't exist (in USD)
        $wallet = \App\Models\Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'locked_balance' => 0,
                'currency' => 'USD',
            ]
        );
        
        // Calculate available balance (total balance - locked balance)
        $availableBalance = $wallet->balance - $wallet->locked_balance;
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'balance' => $wallet->balance,
                'locked_balance' => $wallet->locked_balance,
                'available_balance' => $availableBalance,
                'currency' => $wallet->currency,
            ]
        ]);
    });

    // Admin ambassador routes (CRUD)
    Route::prefix('admin/ambassadors')->group(function () {
        Route::post('/', [AmbassadorController::class, 'store']);      // Create ambassador
        Route::put('/{id}', [AmbassadorController::class, 'update']);    // Update ambassador
        Route::delete('/{id}', [AmbassadorController::class, 'destroy']); // Delete ambassador
    });

    // Admin partner routes (CRUD)
    Route::prefix('admin/partners')->group(function () {
        Route::post('/', [PartnerController::class, 'store']);      // Create partner
        Route::put('/{id}', [PartnerController::class, 'update']);    // Update partner
        Route::delete('/{id}', [PartnerController::class, 'destroy']); // Delete partner
    });

    // Admin game category routes (CRUD)
    Route::prefix('admin/game-categories')->group(function () {
        Route::post('/', [GameCategoryController::class, 'store']);      // Create category
        Route::put('/{id}', [GameCategoryController::class, 'update']);    // Update category
        Route::delete('/{id}', [GameCategoryController::class, 'destroy']); // Delete category
    });

    // Admin game routes (CRUD)
    Route::prefix('admin/games')->group(function () {
        Route::post('/', [GameController::class, 'store']);      // Create game
        Route::put('/{id}', [GameController::class, 'update']);    // Update game
        Route::delete('/{id}', [GameController::class, 'destroy']); // Delete game
    });

    // Admin game match routes (CRUD)
    Route::prefix('admin/game-matches')->group(function () {
        Route::post('/', [GameMatchController::class, 'store']);      // Create match
        Route::put('/{id}', [GameMatchController::class, 'update']);    // Update match
        Route::delete('/{id}', [GameMatchController::class, 'destroy']); // Delete match
    });

    // User bet routes
    Route::prefix('bets')->group(function () {
        Route::get('/', [BetController::class, 'index']);        // List user bets
        Route::post('/', [BetController::class, 'store']);        // Place bet
    });

    // Admin general routes
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);                    // Get statistics
        Route::get('/users', [AdminController::class, 'users']);                    // List users
        Route::post('/users', [AdminController::class, 'createUser']);              // Create user
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);          // Update user
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);      // Delete user
        Route::get('/deposits', [AdminController::class, 'deposits']);              // List deposits
        Route::post('/deposits/{id}/approve', [AdminController::class, 'approveDeposit']);  // Approve deposit
        Route::post('/deposits/{id}/reject', [AdminController::class, 'rejectDeposit']);     // Reject deposit
        Route::get('/withdrawals', [AdminController::class, 'withdrawals']);        // List withdrawals
        Route::post('/withdrawals/{id}/approve', [AdminController::class, 'approveWithdrawal']); // Approve withdrawal
        Route::post('/withdrawals/{id}/reject', [AdminController::class, 'rejectWithdrawal']);    // Reject withdrawal
        Route::get('/certifications', [AdminController::class, 'certifications']);  // List certifications
        Route::get('/certifications/{id}', [AdminController::class, 'getCertification']);  // Get certification details
        Route::post('/certifications/{id}/approve', [AdminController::class, 'approveCertification']);  // Approve certification
        Route::post('/certifications/{id}/reject', [AdminController::class, 'rejectCertification']);     // Reject certification
    });
});