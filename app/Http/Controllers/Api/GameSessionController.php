<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GameSessions\SubmitScoreRequest;
use App\Models\GameSession;
use App\Services\Game\GameSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class GameSessionController extends Controller
{
    public function __construct(
        protected GameSessionService $sessionService
    ) {
    }

    public function show(GameSession $session, Request $request): JsonResponse
    {
        abort_unless($session->players()->where('user_id', $request->user()->id)->exists(), 403);

        return response()->json(
            $session->load(['game', 'players.user', 'result'])
        );
    }

    public function submitScore(SubmitScoreRequest $request, GameSession $session): JsonResponse
    {
        abort_unless($session->players()->where('user_id', $request->user()->id)->exists(), 403);

        try {
            $session = $this->sessionService->submitScore($session, $request->user(), $request->validated()['score']);
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->json([
            'session' => $session->load(['players.user', 'result']),
        ]);
    }
}

