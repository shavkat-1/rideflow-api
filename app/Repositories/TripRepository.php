<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\TripRepositoryInterface;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

final class TripRepository implements TripRepositoryInterface
{
    public function getAll(): Collection
    {
        return Trip::query()
            ->latest()
            ->get();
    }

    public function getByPassengerId(int $passengerId): Collection
    {
        return Trip::query()
            ->where('passenger_id', $passengerId)
            ->latest()
            ->get();
    }

    public function getByDriverId(int $driverId): Collection
    {
        return Trip::query()
            ->where('driver_id', $driverId)
            ->latest()
            ->get();
    }

    public function findById(int $id): ?Trip
    {
        return Trip::query()->find($id);
    }

    public function findByIdForUpdate(int $id): ?Trip
    {
        return Trip::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function create(array $data): Trip
    {
        $trip = Trip::query()->create($data);

        return $trip->refresh();
    }

    public function update(int $id, array $data): Trip
    {
        $trip = Trip::query()->findOrFail($id);

        $trip->update($data);

        return $trip->refresh();
    }

    public function save(Trip $trip): Trip
    {
        $trip->save();

        return $trip->refresh();
    }

    public function delete(int $id): bool
    {
        $trip = Trip::query()->find($id);

        if ($trip === null) {
            return false;
        }

        return (bool) $trip->delete();
    }
}
