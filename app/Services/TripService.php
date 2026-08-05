<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TripEventPublisherInterface;
use App\Contracts\TripRepositoryInterface;
use App\Enums\UserRole;
use App\Events\TripCreatedEventData;
use App\Jobs\SendTripConfirmationJob;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class TripService
{
    private const TRIPS_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly TripRepositoryInterface $tripRepository,
        private readonly TripEventPublisherInterface $tripEventPublisher
    ) {}

    public function getTripsFor(User $user): Collection
    {
        return Cache::remember(
            $this->tripsListCacheKey($user),
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
        $trip = DB::transaction(function () use ($data): Trip {
            $trip = $this->tripRepository->create($data);

            SendTripConfirmationJob::dispatch($trip->id)
                ->delay(now()->addSeconds(30))
                ->afterCommit();

            return $trip;
        });

        $this->tripEventPublisher->publishTripCreated(
            TripCreatedEventData::fromTrip($trip)
        );

        $this->clearListCacheForTrip($trip);

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

        $this->clearTripCache($trip);

        return $trip;
    }

    public function delete(int $id): bool
    {
        $trip = $this->tripRepository->findById($id);

        if ($trip === null) {
            return false;
        }

        $deleted = $this->tripRepository->delete($id);

        if ($deleted) {
            $this->clearTripCache($trip);
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

        $this->clearTripCache($acceptedTrip);

        return $acceptedTrip;
    }

    private function tripCacheKey(int $id): string
    {
        return "trips:{$id}";
    }

    private function tripsListCacheKey(User $user): string
    {
        return "trips:list:{$user->role->value}:{$user->id}";
    }

    private function clearTripCache(Trip $trip): void
    {
        Cache::forget($this->tripCacheKey($trip->id));

        $this->clearListCacheForTrip($trip);
    }

    private function clearListCacheForTrip(Trip $trip): void
    {
        Cache::forget(
            "trips:list:passenger:{$trip->passenger_id}"
        );

        if ($trip->driver_id !== null) {
            Cache::forget(
                "trips:list:driver:{$trip->driver_id}"
            );
        }

        $this->clearAdminListCaches();
    }

    private function clearAdminListCaches(): void
    {
        User::query()
            ->where('role', UserRole::Admin->value)
            ->pluck('id')
            ->each(
                fn (int $adminId): bool => Cache::forget(
                    "trips:list:admin:{$adminId}"
                )
            );
    }
}
