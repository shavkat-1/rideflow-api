<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TripRepositoryInterface;
use App\Enums\UserRole;
use App\Jobs\SendTripConfirmationJob;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class TripService
{
    private const TRIPS_CACHE_KEY = 'trips:list';

    private const TRIPS_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly TripRepositoryInterface $tripRepository
    ) {}

    public function getTripsFor(User $user): Collection
    {
        $cacheKey = $this->tripsListCacheKey($user);

        return Cache::remember(
            $cacheKey,
            self::TRIPS_CACHE_TTL_SECONDS,
            fn (): Collection => match ($user->role) {
                UserRole::Admin => $this->tripRepository->getAll(),

                UserRole::Driver => $this->tripRepository->getByDriverId(
                    $user->id
                ),

                UserRole::Passenger => $this->tripRepository->getByPassengerId(
                    $user->id
                ),
            }
        );
    }

    public function createTrip(array $data): Trip
    {
        $trip = $this->tripRepository->create($data);

        $this->clearListCache();

        SendTripConfirmationJob::dispatch($trip->id)
            ->delay(now()->addSeconds(30))
            ->afterCommit();

        return $trip;
    }

    public function findById(int $id): ?Trip
    {
        return Cache::remember(
            $this->tripCacheKey($id),
            self::TRIPS_CACHE_TTL_SECONDS,
            fn (): ?Trip => $this->tripRepository->findById($id)
        );
    }

    public function update(int $id, array $data): Trip
    {
        $trip = $this->tripRepository->update($id, $data);

        $this->clearTripCache($id);

        return $trip;
    }

    public function delete(int $id): bool
    {
        $deleted = $this->tripRepository->delete($id);

        if ($deleted) {
            $this->clearTripCache($id);
        }

        return $deleted;
    }

    public function acceptTrip(int $tripId, int $driverId): Trip
    {
        $acceptedTrip = DB::transaction(
            function () use ($tripId, $driverId): Trip {
                $trip = $this->tripRepository
                    ->findByIdForUpdate($tripId);

                if ($trip === null) {
                    abort(404, 'Поездка не найдена');
                }

                if ($trip->status !== 'pending') {
                    abort(
                        409,
                        'Поездка уже недоступна для принятия'
                    );
                }

                $trip->driver_id = $driverId;
                $trip->status = 'accepted';

                return $this->tripRepository->save($trip);
            }
        );

        $this->clearTripCache($tripId);

        return $acceptedTrip;
    }

    private function tripCacheKey(int $id): string // Создает уникальный ключ для каждой поездки
    {
        return "trips:{$id}";
    }

    private function clearListCache(): void
    {
        Cache::forget(self::TRIPS_CACHE_KEY);
    }

    private function clearTripCache(int $id): void
    {
        $this->clearListCache();

        Cache::forget($this->tripCacheKey($id));
    }

    private function tripsListCacheKey(User $user): string
    {
        return "trips:list:{$user->role->value}:{$user->id}";
    }
}
