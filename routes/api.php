<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\RoundController;
use App\Http\Controllers\MatchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Protected API Routes (Requires a valid Sanctum token)
Route::middleware('auth:sanctum')->group(function () {

    // --- Tournaments ---
    // Custom endpoint for team registration must be declared BEFORE the resource route
    Route::post('tournaments/{tournament}/register', [TournamentController::class, 'registerTeams']);
    Route::apiResource('tournaments', TournamentController::class);

    // --- Teams ---
    Route::apiResource('teams', TeamController::class);

    // --- Players ---
    Route::apiResource('players', PlayerController::class);

    // --- Rounds ---
    Route::apiResource('rounds', RoundController::class);

    // --- Matches ---
    Route::put('matches/{matchInfo}/score', [MatchController::class, 'updateScore']);
    
    Route::apiResource('matches', MatchController::class)->parameters([
        'matches' => 'matchInfo'
    ]);

    Route::post('tournaments/{tournament}/teams', [TournamentController::class, 'addTeams']);
    Route::post('tournaments/{tournament}/teams/add', [TournamentController::class, 'addSingleTeam']);
    Route::post('tournaments/{tournament}/teams/remove', [TournamentController::class, 'removeTeam']);

});
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

