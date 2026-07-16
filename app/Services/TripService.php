<?php 

namespace App\Services;

use App\Models\Trip;
use App\Contracts\TripRepositoryInterface;
use Illuminate\Database\Eloquent\Collection; 

class TripService 
{
    public function __construct(
        protected TripRepositoryInterface $tripRepository
    ) {}
 
    public function getAll(): Collection 
    {
        return $this->tripRepository->getAll();
    }

}