<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\FcmController;

Route::post('/register', [LoginController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('products', ProductController::class);

// ==========================================
// Rutas de Firebase Cloud Messaging (FCM)
// ==========================================

// Rutas protegidas de FCM
Route::middleware('auth:sanctum')->group(function () {
    // Guardar token FCM (autenticado)
    Route::post('/fcm-token', [FcmController::class, 'storeToken']);
    
    // Obtener tokens del usuario autenticado
    Route::get('/fcm-tokens', [FcmController::class, 'getUserTokens']);
    
    // Eliminar un token específico
    Route::delete('/fcm-tokens/{tokenId}', [FcmController::class, 'deleteToken']);
    
    // Enviar una notificación de prueba
    Route::post('/notifications/test', [FcmController::class, 'sendTestNotification']);
    
    // Broadcast a todos los usuarios (requiere admin)
    Route::post('/notifications/broadcast', [FcmController::class, 'broadcastNotification']);
});
