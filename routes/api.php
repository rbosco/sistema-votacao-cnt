<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rotas públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/vote', [VoteController::class, 'vote']);
Route::get('/proposals/active', [ProposalController::class, 'getActive']);
Route::get('/proposals/{id}/results', [ProposalController::class, 'getResults']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Usuários
    Route::apiResource('users', UserController::class);

    // Propostas
    Route::apiResource('proposals', ProposalController::class)->except(['show']);
    Route::post('/proposals/{id}/activate', [ProposalController::class, 'activate']);
    Route::post('/proposals/{id}/deactivate', [ProposalController::class, 'deactivate']);

    // Votos
    Route::get('/votes', [VoteController::class, 'index']);
    Route::get('/proposals/{id}/votes', [VoteController::class, 'getByProposal']);
});
