<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PetitionController;
use App\Http\Controllers\CategoryController;

// --------------------
// PUBLIC ROUTES
// --------------------
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::get('peticiones', [PetitionController::class, 'index']);
Route::get('peticiones/{id}', [PetitionController::class, 'show']);
Route::get('categorias', [CategoryController::class, 'index']);


// --------------------
// PROTECTED ROUTES (JWT)
// --------------------
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // ✅ FRONT COMPATIBLE
    Route::get('mispeticiones', [PetitionController::class, 'mine']);
    Route::get('misfirmas', [PetitionController::class, 'signed']);

    // CRUD
    Route::post('peticiones', [PetitionController::class, 'store']);

    // Update (FormData _method)
    Route::post('peticiones/{id}', [PetitionController::class, 'update']);
    Route::put('peticiones/{id}', [PetitionController::class, 'update']);

    Route::delete('peticiones/{id}', [PetitionController::class, 'destroy']);

    // Firmar
    Route::put('peticiones/{id}/sign', [PetitionController::class, 'firmar']);
});


// --------------------
// REFRESH TOKEN
// --------------------
Route::post('refresh', [AuthController::class, 'refresh']);
