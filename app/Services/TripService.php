<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TripRepositoryInterface;
use App\Jobs\SendTripConfirmationJob;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class TripService
{
    private const TRIPS_CACHE_KEY = 'trips:list';

    private const TRIPS_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly TripRepositoryInterface $tripRepository
    ) {}

    public function getAll(): Collection
    {
        return Cache::remember(
            self::TRIPS_CACHE_KEY,
            self::TRIPS_CACHE_TTL_SECONDS,
            fn (): Collection => $this->tripRepository->getAll()
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
}
