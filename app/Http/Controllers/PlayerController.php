<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use Illuminate\Http\JsonResponse;

class PlayerController extends Controller
{
    /**
     * Display a listing of the players.
     */
    public function index(): JsonResponse
    {
        // Eager load the team relationship to avoid N+1 queries
        $players = Player::with('team')->latest()->get();

        return response()->json([
            'data' => $players
        ]);
    }

    /**
     * Store a newly created player in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'nullable|date|before:today',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $player = Player::create($validated);

        return response()->json([
            'message' => 'Player added to roster successfully',
            'data' => $player->load('team')
        ], 201);
    }

    /**
     * Display the specified player.
     */
    public function show(Player $player): JsonResponse
    {
        return response()->json([
            'data' => $player->load('team')
        ]);
    }

    /**
     * Update the specified player in storage.
     */
    public function update(Request $request, Player $player): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'birth_date' => 'nullable|date|before:today',
            'team_id' => 'sometimes|required|exists:teams,id',
        ]);

        $player->update($validated);

        return response()->json([
            'message' => 'Player profile updated successfully',
            'data' => $player->load('team')
        ]);
    }

    /**
     * Remove the specified player from storage.
     */
    public function destroy(Player $player): JsonResponse
    {
        // Note: Your migration handles cascading deletes, meaning if a team is deleted, 
        // the players are removed automatically. This method handles direct player removal.
        $player->deleteOrFail();

        return response()->json([
            'message' => 'Player removed from roster successfully'
        ], 200);
    }
}
