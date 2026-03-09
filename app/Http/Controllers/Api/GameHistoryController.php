<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class GameHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        try {
            // Vérifier si la table existe
            if (!Schema::hasTable('game_history')) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Table game_history does not exist yet. Run migrations first.',
                ]);
            }
            
            // Récupérer l'historique depuis la table game_history
            $history = DB::table('game_history')
                ->where('user_id', $user->id)
                ->orderBy('played_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'game_name' => $item->game_name,
                        'game_slug' => $item->game_slug,
                        'score' => $item->score,
                        'total_rounds' => $item->total_rounds,
                        'difficulty' => $item->difficulty,
                        'stake' => $item->stake ? (float) $item->stake : null,
                        'reward' => $item->reward ? (float) $item->reward : null,
                        'currency' => $item->currency,
                        'status' => $item->status,
                        'played_at' => $item->played_at,
                        'created_at' => $item->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Error fetching history: ' . $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'game_name' => 'required|string|max:255',
            'game_slug' => 'required|string|max:255',
            'score' => 'required|integer|min:0',
            'total_rounds' => 'required|integer|min:1',
            'difficulty' => 'required|string|max:50',
            'stake' => 'nullable|numeric|min:0',
            'reward' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'status' => 'nullable|string|in:won,lost,completed',
            'played_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if (!Schema::hasTable('game_history')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table game_history does not exist. Run migrations first.',
                ], 500);
            }

            $user = $request->user();
            
            $id = DB::table('game_history')->insertGetId([
                'user_id' => $user->id,
                'game_name' => $request->game_name,
                'game_slug' => $request->game_slug,
                'score' => $request->score,
                'total_rounds' => $request->total_rounds,
                'difficulty' => $request->difficulty,
                'stake' => $request->stake,
                'reward' => $request->reward,
                'currency' => $request->currency,
                'status' => $request->status ?? 'completed',
                'played_at' => $request->played_at ? date('Y-m-d H:i:s', strtotime($request->played_at)) : now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Game history saved',
                'data' => ['id' => $id],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving history: ' . $e->getMessage(),
            ], 500);
        }
    }
}
