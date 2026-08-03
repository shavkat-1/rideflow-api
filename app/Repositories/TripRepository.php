<?php

namespace App\Repositories;

use App\Contracts\TripRepositoryInterface;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

class TripRepository implements TripRepositoryInterface
{
    public function getAll(): Collection
    {
        return Trip::query()
            ->latest()
            ->get();
    }

    public function create(array $data): Trip
    {
        return Trip::query()->create($data);
    }

    public function findById(int $id): ?Trip
    {
        return Trip::query()->find($id);
    }

    public function update(int $id, array $data): Trip
    {
        $trip = Trip::query()->findOrFail($id);

        $trip->update($data);

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
