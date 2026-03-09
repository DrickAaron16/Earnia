<?php

use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\TournamentController as AdminTournamentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GameHistoryController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\Api\MatchmakingController;
use App\Http\Controllers\Api\PaygateWebhookController;
use App\Http\Controllers\Api\PaymentMockController;
use App\Http\Controllers\Api\TournamentController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

Route::get('/docs', function () {
    return view('l5-swagger::index');
});

// Public routes - Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/social', [AuthController::class, 'socialLogin']);
Route::post('/auth/firebase', [AuthController::class, 'firebaseLogin']);

// Public games list (for testing)
Route::get('games', [GameController::class, 'index']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('games/start', [GameController::class, 'start']);

    Route::prefix('wallet')->group(function (): void {
        Route::get('/', [WalletController::class, 'show']);
        Route::get('transactions', [WalletController::class, 'transactions']);
        Route::get('deposits', [WalletController::class, 'deposits']);
        Route::get('withdrawals', [WalletController::class, 'withdrawals']);
        Route::post('deposits', [WalletController::class, 'requestDeposit']);
        Route::post('withdrawals', [WalletController::class, 'requestWithdrawal']);
    });

    Route::prefix('matchmaking')->group(function (): void {
        Route::get('tickets', [MatchmakingController::class, 'index']);
        Route::post('tickets', [MatchmakingController::class, 'store']);
        Route::delete('tickets/{ticket}', [MatchmakingController::class, 'destroy']);
    });

    Route::prefix('tournaments')->group(function (): void {
        Route::get('/', [TournamentController::class, 'index']);
        Route::get('{tournament}', [TournamentController::class, 'show']);
        Route::get('{tournament}/entries', [TournamentController::class, 'entries']);
        Route::post('{tournament}/register', [TournamentController::class, 'register']);
        Route::post('{tournament}/withdraw', [TournamentController::class, 'withdraw']);
    });

    Route::get('game-sessions/{session}', [GameSessionController::class, 'show']);
    Route::post('game-sessions/{session}/score', [GameSessionController::class, 'submitScore']);
    
    Route::get('game-history', [GameHistoryController::class, 'index']);
    Route::post('game-history', [GameHistoryController::class, 'store']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::get('payments/deposits', [AdminPaymentController::class, 'deposits']);
    Route::post('payments/deposits/{deposit}/confirm', [AdminPaymentController::class, 'confirmDeposit']);
    Route::post('payments/deposits/{deposit}/fail', [AdminPaymentController::class, 'failDeposit']);
    Route::get('payments/withdrawals', [AdminPaymentController::class, 'withdrawals']);
    Route::post('payments/withdrawals/{withdrawal}/approve', [AdminPaymentController::class, 'approveWithdrawal']);
    Route::post('payments/withdrawals/{withdrawal}/reject', [AdminPaymentController::class, 'rejectWithdrawal']);

    Route::get('tournaments', [AdminTournamentController::class, 'index']);
    Route::post('tournaments', [AdminTournamentController::class, 'store']);
    Route::put('tournaments/{tournament}', [AdminTournamentController::class, 'update']);
    Route::post('tournaments/{tournament}/publish', [AdminTournamentController::class, 'publish']);
    Route::post('tournaments/{tournament}/cancel', [AdminTournamentController::class, 'cancel']);
    Route::get('tournaments/{tournament}/entries', [AdminTournamentController::class, 'entries']);
    Route::post('tournaments/{tournament}/settle', [AdminTournamentController::class, 'settle']);
});

Route::post('payments/mock/{provider}', PaymentMockController::class);
Route::post('payments/paygate/callback', PaygateWebhookController::class)->name('payments.paygate.callback');

