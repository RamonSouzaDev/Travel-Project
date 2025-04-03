<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TravelRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas públicas de autenticação
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware('auth:api')->group(function () {

    // Rotas de autenticação
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    
    // Rotas de pedidos de viagem
    Route::get('travel-requests', [TravelRequestController::class, 'index']);
    Route::post('travel-requests', [TravelRequestController::class, 'store']);
    Route::get('travel-requests/{id}', [TravelRequestController::class, 'show']);
    Route::patch('travel-requests/{id}/status', [TravelRequestController::class, 'updateStatus']);
    Route::post('travel-requests/{id}/cancel', [TravelRequestController::class, 'cancel']);
});
