<?php

namespace App\Http\Controllers\Api;

use App\Services\TripService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function __construct(
       protected TripService $tripService
    ) {}


    public function index()
    {
        return $this->tripService->getAll();
    }
}
