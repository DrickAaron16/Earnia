<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\SettleTournamentRequest;
use App\Http\Requests\Tournaments\StoreTournamentRequest;
use App\Http\Requests\Tournaments\UpdateTournamentRequest;
use App\Models\Tournament;
use App\Services\Tournament\TournamentPayoutService;
use App\Services\Tournament\TournamentService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TournamentController extends Controller
{
    public function __construct(
        protected TournamentService $tournamentService,
        protected TournamentPayoutService $payoutService
    ) {
    }

    public function index(): JsonResponse
    {
        $tournaments = Tournament::query()
            ->with('game')
            ->withCount('entries')
            ->latest()
            ->paginate(25);

        return response()->json($tournaments);
    }

    public function store(StoreTournamentRequest $request): JsonResponse
    {
        $tournament = $this->tournamentService->create($request->validated());

        return response()->json([
            'tournament' => $tournament->load('game'),
        ], 201);
    }

    public function update(UpdateTournamentRequest $request, Tournament $tournament): JsonResponse
    {
        $tournament->update($request->validated());

        return response()->json([
            'tournament' => $tournament->fresh()->load('game'),
        ]);
    }

    public function publish(Tournament $tournament): JsonResponse
    {
        try {
            $tournament = $this->tournamentService->publish($tournament);
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->json(['tournament' => $tournament]);
    }

    public function cancel(Tournament $tournament): JsonResponse
    {
        try {
            $tournament = $this->tournamentService->cancel($tournament);
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->json(['tournament' => $tournament]);
    }

    public function entries(Tournament $tournament): JsonResponse
    {
        return response()->json(
            $tournament->entries()->with('user')->paginate(50)
        );
    }

    public function settle(SettleTournamentRequest $request, Tournament $tournament): JsonResponse
    {
        try {
            $tournament = $this->payoutService->settle($tournament, $request->validated()['results']);
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->json([
            'tournament' => $tournament,
        ]);
    }
}

