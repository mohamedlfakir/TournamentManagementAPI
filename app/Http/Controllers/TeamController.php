<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    /**
     * Display a listing of the teams.
     */
    public function index(): JsonResponse
    {
        // Load count of players to give simple info on index list
        $teams = Team::withCount('players')->latest()->get();

        return response()->json([
            'data' => $teams
        ]);
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'description' => 'nullable|string',
        ]);

        $team = Team::create($validated);

        return response()->json([
            'message' => 'Team created successfully',
            'data' => $team
        ], 201);
    }

    /**
     * Display the specified team with its players roster.
     */
    public function show(Team $team): JsonResponse
    {
        // Load full list of players assigned to this team
        $team->load('players');

        return response()->json([
            'data' => $team
        ]);
    }

    /**
     * Update the specified team in storage.
     */
    public function update(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:teams,name,' . $team->id,
            'description' => 'nullable|string',
        ]);

        $team->update($validated);

        return response()->json([
            'message' => 'Team updated successfully',
            'data' => $team
        ]);
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Team $team): JsonResponse
    {
        // Check if the team is bound to tournaments before deleting if cascading isn't set up
        $team->tournaments()->detach();
        $team->deleteOrFail();

        return response()->json([
            'message' => 'Team deleted successfully'
        ], 200);
    }
}
