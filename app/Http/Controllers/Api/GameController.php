<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\GameSessionPlayer;
use App\Models\MatchmakingTicket;
use App\Services\Game\GameSessionService;
use App\Services\Game\MatchmakingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{
    public function __construct(
        protected MatchmakingService $matchmakingService,
        protected GameSessionService $gameSessionService
    ) {
    }

    public function index(): JsonResponse
    {
        $games = Game::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $games
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'game_id' => ['required', 'exists:games,id'],
            'stake_amount' => ['required', 'numeric', 'min:0'],
            'mode' => ['nullable', 'string', 'in:solo,duel,multiplayer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $game = Game::findOrFail($request->game_id);
        $mode = $request->mode ?? $game->default_mode;
        $stakeAmount = (float) $request->stake_amount;

        // Validate stake amount
        if ($stakeAmount < $game->min_stake) {
            return response()->json([
                'success' => false,
                'message' => "La mise minimale est de {$game->min_stake}"
            ], 400);
        }

        if ($game->max_stake && $stakeAmount > $game->max_stake) {
            return response()->json([
                'success' => false,
                'message' => "La mise maximale est de {$game->max_stake}"
            ], 400);
        }

        try {
            $result = $this->matchmakingService->createTicket($user, $game, [
                'mode' => $mode,
                'stake_amount' => $stakeAmount,
                'max_players' => $game->max_players,
            ]);

            if ($result['session']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Partie démarrée',
                    'data' => [
                        'session' => $result['session'],
                        'ticket' => $result['ticket'],
                    ]
                ], 201);
            }

            return response()->json([
                'success' => true,
                'message' => 'En attente d\'adversaires',
                'data' => [
                    'ticket' => $result['ticket'],
                    'waiting' => true,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage du jeu: ' . $e->getMessage()
            ], 500);
        }
    }
}
