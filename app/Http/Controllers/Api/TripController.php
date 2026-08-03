<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripStoreRequest;
use App\Http\Requests\TripUpdateRequest;
use App\Http\Resources\TripResource;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TripController extends Controller
{
    public function __construct(
        private readonly TripService $tripService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return TripResource::collection(
            $this->tripService->getAll()
        );
    }

    public function store(TripStoreRequest $request): JsonResponse
    {
        $trip = $this->tripService->createTrip(
            $request->validated()
        );

        return response()->json([
            'message' => 'Поездка создана. Подтверждение будет отправлено позже.',
            'data' => new TripResource($trip),
        ], 201);
    }

    public function show(int $id): TripResource|JsonResponse
    {
        $trip = $this->tripService->findById($id);

        if ($trip === null) {
            return response()->json([
                'message' => 'Поездка не найдена',
            ], 404);
        }

        return new TripResource($trip);
    }

    public function update(TripUpdateRequest $request, int $id): TripResource
    {
        $trip = $this->tripService->update(
            $id,
            $request->validated()
        );

        return new TripResource($trip);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->tripService->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Поездка не найдена',
            ], 404);
        }

        return response()->json([
            'message' => 'Поездка удалена',
        ]);
    }
}
