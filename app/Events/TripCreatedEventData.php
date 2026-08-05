<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Trip;

final readonly class TripCreatedEventData
{
    public function __construct(
        public int $tripId,
        public int $passengerId,
        public string $status,
        public string $occurredAt,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'event' => 'trip.created',
            'trip_id' => $this->tripId,
            'passenger_id' => $this->passengerId,
            'status' => $this->status,
            'occurred_at' => $this->occurredAt,
        ];
    }

    public static function fromTrip(Trip $trip): self
    {
        return new self(
            tripId: $trip->id,
            passengerId: $trip->passenger_id,
            status: $trip->status,
            occurredAt: $trip->created_at->toISOString(),
        );
    }
}
