<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublicServiceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ClientController;

// --- RUTAS PÚBLICAS (Web comercial / Landing) ---
// Accesibles en: /api/public/...
Route::prefix('public')->group(function () {
    Route::get('/services', [PublicServiceController::class, 'index']);
    // Route::post('/appointments', [PublicAppointmentController::class, 'store']);
});

// --- AUTENTICACIÓN BACKOFFICE ---
// Accesible en: POST /api/auth/login
Route::post('/auth/login', [AuthController::class, 'login']);

// --- RUTAS PRIVADAS (App interna / Gestión administrativa) ---
// Requieren token Bearer
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Clientes (/api/clients, /api/clients/{id}, etc.)
    Route::apiResource('clients', ClientController::class);

    // Facturación (/api/invoices)
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
});