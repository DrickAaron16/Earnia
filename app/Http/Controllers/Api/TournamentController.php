<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Services\Tournament\TournamentEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TournamentController extends Controller
{
    public function __construct(
        protected TournamentEntryService $entryService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tournaments = Tournament::query()
            ->whereIn('status', ['upcoming', 'running'])
            ->withCount('entries')
            ->orderBy('starts_at')
            ->paginate(20);

        return response()->json($tournaments);
    }

    public function show(Tournament $tournament): JsonResponse
    {
        return response()->json(
            $tournament->load(['game', 'entries' => fn ($query) => $query->with('user')->orderByDesc('payout_amount')])
        );
    }

    public function register(Tournament $tournament, Request $request): JsonResponse
    {
        try {
            $entry = $this->entryService->register($tournament, $request->user());
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->json([
            'entry' => $entry,
        ], 201);
    }

    public function withdraw(Tournament $tournament, Request $request): JsonResponse
    {
        $entry = $tournament->entries()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        try {
            $entry = $this->entryService->withdraw($entry);
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->json([
            'entry' => $entry,
        ]);
    }

    public function entries(Tournament $tournament): JsonResponse
    {
        $entries = $tournament->entries()
            ->with('user')
            ->latest('registered_at')
            ->paginate(50);

        return response()->json($entries);
    }
}

