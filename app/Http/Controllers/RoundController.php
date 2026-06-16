<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Round;
use Illuminate\Http\JsonResponse;

class RoundController extends Controller
{
    /**
     * Display a listing of rounds.
     */
    public function index(): JsonResponse
    {
        $rounds = Round::with(['tournament'])->orderBy('tournament_id')->orderBy('round_number')->get();

        return response()->json([
            'data' => $rounds
        ]);
    }

    /**
     * Store a newly created round in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'name' => 'required|string|max:255',
            'round_number' => 'required|integer|min:1',
        ]);

        $round = Round::create($validated);

        return response()->json([
            'message' => 'Tournament round created successfully',
            'data' => $round
        ], 201);
    }

    /**
     * Display the specified round with its matches.
     */
    public function show(Round $round): JsonResponse
    {
        // Eager load the matches and their opposing teams for this round
        return response()->json([
            'data' => $round->load(['matches.homeTeam', 'matches.awayTeam'])
        ]);
    }

    /**
     * Update the specified round in storage.
     */
    public function update(Request $request, Round $round): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id' => 'sometimes|required|exists:tournaments,id',
            'name' => 'sometimes|required|string|max:255',
            'round_number' => 'sometimes|required|integer|min:1',
        ]);

        $round->update($validated);

        return response()->json([
            'message' => 'Round updated successfully',
            'data' => $round
        ]);
    }

    /**
     * Remove the specified round from storage.
     */
    public function destroy(Round $round): JsonResponse
    {
        // Note: Your migration has onDelete('cascade'), which means 
        // removing this round automatically cleans up its associated match_infos.
        $round->deleteOrFail();

        return response()->json([
            'message' => 'Round and all associated matches deleted successfully'
        ], 200);
    }
}
