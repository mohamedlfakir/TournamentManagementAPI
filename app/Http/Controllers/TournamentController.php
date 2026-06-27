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

        $this->generateBracketStructure($tournament);

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
     * Associate teams to a tournament without exceeding maximum slots.
     */
    public function addTeams(Request $request, Tournament $tournament): JsonResponse
    {
        // 1. Validate that team_ids are provided as an array and exist in the database
        $validated = $request->validate([
            'team_ids' => 'required|array',
            'team_ids.*' => 'required|integer|exists:teams,id',
        ]);

        $newTeamIds = array_unique($validated['team_ids']);
        
        // 2. Fetch currently registered teams
        $currentTeamsCount = $tournament->teams()->count();
        
        // 3. Filter out teams that are ALREADY in this tournament to prevent double counting
        $alreadyAttachedIds = $tournament->teams()->pluck('teams.id')->toArray();
        $filteredNewTeamIds = array_diff($newTeamIds, $alreadyAttachedIds);
        
        $incomingCount = count($filteredNewTeamIds);

        if ($incomingCount === 0) {
            return response()->json([
                'message' => 'All provided teams are already registered in this tournament.'
            ], 422);
        }

        // 4. Check if adding these teams exceeds total allowed slots
        if (($currentTeamsCount + $incomingCount) > $tournament->slots) {
            $availableSlots = $tournament->slots - $currentTeamsCount;
            
            return response()->json([
                'error' => 'Slot Limit Exceeded',
                'message' => "Cannot add {$incomingCount} teams. Only {$availableSlots} out of {$tournament->slots} slots are remaining."
            ], 422);
        }

        // 5. Attach the teams (Using many-to-many pivot logic)
        $tournament->teams()->syncWithoutDetaching($filteredNewTeamIds);

        return response()->json([
            'message' => "Successfully added {$incomingCount} team(s) to the tournament.",
            'current_count' => $tournament->teams()->count(),
            'max_slots' => $tournament->slots
        ], 200);
    }


    /**
     * Associate exactly one team to a tournament checking maximum slots.
     */
    public function addSingleTeam(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
        ]);

        $teamId = $validated['team_id'];

        // 1. Check if the team is already enrolled
        if ($tournament->teams()->where('teams.id', $teamId)->exists()) {
            return response()->json([
                'error' => 'Already Enrolled',
                'message' => 'This team is already registered in this tournament.'
            ], 422);
        }

        // 2. Fetch current slot capacity
        $currentTeamsCount = $tournament->teams()->count();

        // 3. Confirm slot availability
        if ($currentTeamsCount >= $tournament->slots) {
            return response()->json([
                'error' => 'Slot Limit Exceeded',
                'message' => "The tournament is already full. Cannot exceed {$tournament->slots} slots."
            ], 422);
        }

        // 4. Enroll the team
        $tournament->teams()->attach($teamId);

        return response()->json([
            'message' => 'Team registered successfully.',
            'current_count' => $currentTeamsCount + 1,
            'max_slots' => $tournament->slots
        ], 200);
    }

    /**
     * Remove (detach) a single team from the tournament.
     */
    public function removeTeam(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
        ]);

        $teamId = $validated['team_id'];

        // 1. Verify that the team is indeed enrolled
        if (!$tournament->teams()->where('teams.id', $teamId)->exists()) {
            return response()->json([
                'error' => 'Not Registered',
                'message' => 'This team is not registered in this tournament.'
            ], 422);
        }

        // 2. Detach team from pivot tables
        $tournament->teams()->detach($teamId);

        return response()->json([
            'message' => 'Team successfully removed from tournament.',
            'current_count' => $tournament->teams()->count(),
            'max_slots' => $tournament->slots
        ], 200);
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

    /**
     * Internal Controller Helper: Generate all rounds and match placeholders.
     */
    private function generateBracketStructure(Tournament $tournament): void
    {
        $slots = (int) $tournament->slots;
        
        // Calculate the depth of the tree (e.g., log2(16) = 4 rounds)
        $totalRounds = log($slots, 2);

        for ($roundNum = 1; $roundNum <= $totalRounds; $roundNum++) {
            
            // Generate clean names depending on the remaining matches
            $roundsLeft = $totalRounds - $roundNum;
            $roundName = match ($roundsLeft) {
                0.0 => 'Final',
                1.0 => 'Semifinals',
                2.0 => 'Quarterfinals',
                default => "Round of " . pow(2, $roundsLeft + 1),
            };

            // Save the Round instance
            $round = $tournament->rounds()->create([
                'name' => $roundName,
                'round_number' => $roundNum,
            ]);

            // Determine how many matches exist in this round tier
            $matchesInRound = $slots / pow(2, $roundNum);

            for ($matchNum = 1; $matchNum <= $matchesInRound; $matchNum++) {
                $round->matches()->create([
                    'home_team_id' => null,
                    'away_team_id' => null,
                    'match_number' => $matchNum,
                    'status' => 'scheduled',
                    'tournament_id' => $tournament->id,
                ]);
            }
        }
    }
}
