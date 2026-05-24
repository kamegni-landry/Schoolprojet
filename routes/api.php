<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SignalementController;
use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\RamassageController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes – DoualaClean
|--------------------------------------------------------------------------
| IMPORTANT : les routes statiques (carte, stats) sont déclarées AVANT
| les routes avec wildcard {id} pour éviter les conflits de routing.
*/

// ──────────────────────────────────────────────
// ROUTES PUBLIQUES (sans token)
// ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// Route publique pour la carte (visualisation sans connexion)
Route::get('/signalements/carte', [SignalementController::class, 'carte']);

// ──────────────────────────────────────────────
// ROUTES PROTÉGÉES (token Sanctum requis)
// ──────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',   [AuthController::class, 'logout']);
        Route::get('/me',        [AuthController::class, 'me']);
        Route::put('/profile',   [AuthController::class, 'updateProfile']);
        Route::put('/password',  [AuthController::class, 'changePassword']);
    });

    // Signalements — routes statiques EN PREMIER pour éviter {id} de les capturer
    Route::get('/signalements/stats/globales',    [SignalementController::class, 'stats']);
    Route::get('/abonnements/mon-abonnement',     [AbonnementController::class, 'monAbonnement']);
    Route::get('/ramassage/mon-service',          [RamassageController::class, 'monService']);

    // Signalements CRUD
    Route::get('/signalements',                   [SignalementController::class, 'index']);
    Route::post('/signalements',                  [SignalementController::class, 'store']);
    Route::get('/signalements/{id}',              [SignalementController::class, 'show']);
    Route::patch('/signalements/{id}/statut',     [SignalementController::class, 'changerStatut']);
    Route::delete('/signalements/{id}',           [SignalementController::class, 'destroy']);

    // Abonnements
    Route::post('/abonnements',                   [AbonnementController::class, 'choisirPlan']);
    Route::delete('/abonnements/{id}',            [AbonnementController::class, 'annuler']);

    // Ramassage
    Route::post('/ramassage',                     [RamassageController::class, 'store']);
    Route::delete('/ramassage/{id}',              [RamassageController::class, 'annuler']);

    // ──────────────────────────────────────────────
    // ROUTES ADMIN UNIQUEMENT
    // ──────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard',              [AdminController::class, 'dashboard']);
        Route::get('/users',                  [AdminController::class, 'listeUsers']);
        Route::post('/users',                 [AdminController::class, 'creerUser']);
        Route::patch('/users/{id}/toggle',    [AdminController::class, 'toggleUser']);
        Route::delete('/users/{id}',          [AdminController::class, 'supprimerUser']);
        Route::get('/abonnements',            [AbonnementController::class, 'index']);
        Route::get('/ramassage',              [RamassageController::class, 'index']);
    });
});
