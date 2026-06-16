<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tournament;
use App\Models\Round;
use App\Models\MatchInfo;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class TournamentController extends Controller
{
    /**
     * Display a listing of the tournaments.
     */
    public function index(): JsonResponse
    {
        // Eager load rounds to avoid N+1 query problems
        $tournaments = Tournament::with('rounds')->latest()->get();
        
        return response()->json([
            'data' => $tournaments
        ]);
    }

    /**
     * Store a newly created tournament in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'sometimes|in:upcoming,ongoing,completed',
            'slots' => 'sometimes|in:8,16,32',
        ]);

        $tournament = Tournament::create($validated);

        // NOTE: This is where you would call your service class 
        // to automatically generate the Rounds and Matches bracket!
        // BracketGenerationService::generate($tournament);

        return response()->json([
            'message' => 'Tournament created successfully',
            'data' => $tournament
        ], 201);
    }

    /**
     * Display the specified tournament with its bracket data.
     */
    public function show(Tournament $tournament): JsonResponse
    {
        // Load the full bracket structure: Rounds -> Matches -> Teams
        $tournament->load([
            'rounds.matches.homeTeam', 
            'rounds.matches.awayTeam', 
            'teams'
        ]);

        return response()->json([
            'data' => $tournament
        ]);
    }

    /**
     * Update the specified tournament.
     */
    public function update(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'status' => 'sometimes|in:upcoming,ongoing,completed',
            'slots' => 'sometimes|in:8,16,32',
        ]);

        $tournament->update($validated);

        return response()->json([
            'message' => 'Tournament updated successfully',
            'data' => $tournament
        ]);
    }

    /**
     * Remove the specified tournament from storage.
     */
    public function destroy(Tournament $tournament): JsonResponse
    {
        $tournament->deleteOrFail();

        return response()->json([
            'message' => 'Tournament deleted successfully'
        ], 200);
    }
}
