<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MatchInfo;
use Illuminate\Http\JsonResponse;
class MatchController extends Controller
{
    /**
     * Display a listing of matches.
     */
    public function index(): JsonResponse
    {
        $matches = MatchInfo::with(['homeTeam', 'awayTeam', 'round'])
            ->orderBy('match_date', 'asc')
            ->get();

        return response()->json([
            'data' => $matches
        ]);
    }

    /**
     * Store a newly created match.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'round_id' => 'required|exists:rounds,id',
            'home_team_id' => 'nullable|exists:teams,id',
            'away_team_id' => 'nullable|exists:teams,id',
            'match_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $match = MatchInfo::create($validated);

        return response()->json([
            'message' => 'Match slot scheduled successfully',
            'data' => $match->load(['homeTeam', 'awayTeam'])
        ], 201);
    }

    /**
     * Display a specific match with team details.
     */
    public function show(MatchInfo $matchInfo): JsonResponse
    {
        return response()->json([
            'data' => $matchInfo->load(['homeTeam', 'awayTeam', 'tournament', 'round'])
        ]);
    }

    /**
     * General update for match details (e.g., changing date or location).
     */
    public function update(Request $request, MatchInfo $matchInfo): JsonResponse
    {
        $validated = $request->validate([
            'home_team_id' => 'nullable|exists:teams,id',
            'away_team_id' => 'nullable|exists:teams,id',
            'match_date' => 'sometimes|required|date',
            'location' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:scheduled,completed',
            'winner' => 'nullable|in:home,away,draw',
            'sort_order' => 'nullable|integer',
        ]);

        $matchInfo->update($validated);

        return response()->json([
            'message' => 'Match details updated successfully',
            'data' => $matchInfo
        ]);
    }

    /**
     * Explicit endpoint to report scores and automatically resolve the winner.
     * Route Example: PUT /api/matches/{matchInfo}/score
     */
    public function updateScore(Request $request, MatchInfo $matchInfo): JsonResponse
    {
        $validated = $request->validate([
            'home_team_score' => 'required|integer|min:0',
            'away_team_score' => 'required|integer|min:0|different:home_team_score', 
        ]);

        // Determine winner based on the higher score (Knockout rules: no draws allowed)
        $winner = $validated['home_team_score'] > $validated['away_team_score'] ? 'home' : 'away';

        $matchInfo->update([
            'home_team_score' => $validated['home_team_score'],
            'away_team_score' => $validated['away_team_score'],
            'winner' => $winner,
            'status' => 'completed',
        ]);

        // NOTE: This is where you would call a progression service to push the winning team's 
        // ID into the next round's match based on this match's `sort_order`.
        // BracketProgressionService::advanceTeam($matchInfo);

        return response()->json([
            'message' => 'Match scores recorded and winner determined',
            'data' => $matchInfo->load(['homeTeam', 'awayTeam'])
        ]);
    }

    /**
     * Remove a match from the schedule.
     */
    public function destroy(MatchInfo $matchInfo): JsonResponse
    {
        $matchInfo->deleteOrFail();

        return response()->json([
            'message' => 'Match deleted successfully'
        ], 200);
    }
}
