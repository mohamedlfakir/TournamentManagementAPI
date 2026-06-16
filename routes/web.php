<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web'])->controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login',  'login');
    Route::post('/logout',  'logout')->middleware('auth:sanctum');
});
