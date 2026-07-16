<?php 

namespace App\Repositories;

use App\Models\Trip;
use App\Contracts\TripRepositoryInterface;
use Illuminate\Database\Eloquent\Collection; 


class TripRepository implements TripRepositoryInterface
{
    public function getAll(): Collection
    {
        return Trip::all();
    }


    public function findById(int $id): ?Trip 
    {
        return Trip::find($id);
    }


    public function create(array $data): Trip
    {
        return Trip::create($data);
    } 


    public function update(int $id, array $data): Trip 
    {
        $trip = Trip::findOrFail($id);

        $trip->update($data);

        return $trip;
    }


    public function delete(int $id): bool 
    {
        $trip = Trip::find($id);
        if ($trip === null) {
        return false;
        }
        return $trip->delete();
    }

}