<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PetitionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminCategoriasController;
use App\Http\Controllers\AdminUsersController;

// --------------------
// PUBLIC ROUTES
// --------------------
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::get('peticiones', [PetitionController::class, 'index']);
Route::get('peticiones/{id}', [PetitionController::class, 'show']);
Route::get('categorias', [CategoryController::class, 'index']);

// Ruta de Refresh (Fuera de auth:api para que pueda renovar)
Route::post('refresh', [AuthController::class, 'refresh']);


// --------------------
// PROTECTED ROUTES (Usuarios Logueados - JWT)
// --------------------
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // Peticiones del usuario normal 
    Route::get('mispeticiones', [PetitionController::class, 'mine']);
    Route::get('misfirmas', [PetitionController::class, 'signed']);

    // CRUD estándar
    Route::post('peticiones', [PetitionController::class, 'store']);
    Route::post('peticiones/{id}', [PetitionController::class, 'update']);
    Route::put('peticiones/{id}', [PetitionController::class, 'update']);
    Route::delete('peticiones/{id}', [PetitionController::class, 'destroy']);
    Route::put('peticiones/{id}/sign', [PetitionController::class, 'firmar']);

// --------------------
    // RUTAS DE ADMINISTRADOR
    // --------------------
    Route::middleware(['is_admin'])->prefix('admin')->group(function () {

        // Listar TODAS las peticiones
        Route::get('peticiones', [AdminController::class, 'indexPeticiones']);

        // Ver detalle de una petición
        Route::get('peticiones/{id}', [AdminController::class, 'showPeticion']);

        // ACTUALIZAR petición
        Route::put('peticiones/{id}', [AdminController::class, 'updatePeticion']);

        // Eliminar petición
        Route::delete('peticiones/{id}', [AdminController::class, 'destroyPeticion']);

        // Categorías
        Route::get('categorias', [AdminCategoriasController::class, 'index']);
        Route::post('categorias', [AdminCategoriasController::class, 'store']);
        Route::put('categorias/{id}', [AdminCategoriasController::class, 'update']);
        Route::delete('categorias/{id}', [AdminCategoriasController::class, 'destroy']);

        // Usuarios
        Route::get('/users', [AdminUsersController::class, 'getUsers']);
        Route::get('/users/{id}', [AdminUsersController::class, 'showUser']);
        Route::put('/users/{id}', [AdminUsersController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminUsersController::class, 'destroyUser']);
    });
});
