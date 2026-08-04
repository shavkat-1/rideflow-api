<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/trips', [TripController::class, 'index']);

    Route::patch('/trips/{trip}', [TripController::class, 'update']);

    Route::delete('/trips/{trip}', [TripController::class, 'destroy']);

    Route::patch('/trips/{id}/accept', [TripController::class, 'accept'])
        ->middleware('driver');
});

Route::post('/trips', [TripController::class, 'store']);

Route::get('/trips/{id}', [TripController::class, 'show']);
