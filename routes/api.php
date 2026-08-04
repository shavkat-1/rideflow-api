<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/me', [AuthController::class, 'me'])
    ->middleware('auth:api');

Route::patch('/trips/{id}/accept', [TripController::class, 'accept'])
    ->middleware(['auth:api', 'driver']);

Route::patch('/trips/{trip}', [TripController::class, 'update'])
    ->middleware('auth:api');

Route::delete('/trips/{trip}', [TripController::class, 'destroy'])
    ->middleware('auth:api');

Route::get('/trips', [TripController::class, 'index']);

Route::post('/trips', [TripController::class, 'store']);

Route::get('/trips/{id}', [TripController::class, 'show']);
