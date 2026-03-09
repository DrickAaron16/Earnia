<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Matchmaking\CreateTicketRequest;
use App\Models\Game;
use App\Models\MatchmakingTicket;
use App\Services\Matchmaking\MatchmakingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MatchmakingController extends Controller
{
    public function __construct(
        protected MatchmakingService $matchmakingService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tickets = MatchmakingTicket::query()
            ->where('user_id', $request->user()->id)
            ->with('game')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    public function store(CreateTicketRequest $request): JsonResponse
    {
        $data = $request->validated();
        $game = Game::findOrFail($data['game_id']);

        try {
            $result = $this->matchmakingService->createTicket($request->user(), $game, $data);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ], 201);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 422);
        }
    }

    public function destroy(MatchmakingTicket $ticket, Request $request): JsonResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        try {
            $this->matchmakingService->cancelTicket($ticket);
            
            return response()->json([
                'success' => true,
                'message' => 'Ticket cancelled successfully'
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 422);
        }
    }
}

