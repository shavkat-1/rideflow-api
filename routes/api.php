<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use App\Services\RequestCounter;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/trips', [TripController::class, 'index']);
    Route::post('/trips', [TripController::class, 'store']);

    Route::patch('/trips/{id}/accept', [TripController::class, 'accept'])
        ->whereNumber('id')
        ->middleware('driver');

    Route::get('/trips/{trip}', [TripController::class, 'show'])
        ->whereNumber('trip');

    Route::patch('/trips/{trip}', [TripController::class, 'update'])
        ->whereNumber('trip');

    Route::delete('/trips/{trip}', [TripController::class, 'destroy'])
        ->whereNumber('trip');
});

// Это демонстрационный endpoint, поэтому я разрешил его только в local environment. В production он регистрироваться не будет.
if (app()->environment('local')) {
    Route::get('/demo/octane-counter', function (
        RequestCounter $counter
    ): array {
        return [
            'count' => $counter->increment(),
        ];
    });
}
