<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TripController;


Route::get('/trips', [TripController::class, 'index']);