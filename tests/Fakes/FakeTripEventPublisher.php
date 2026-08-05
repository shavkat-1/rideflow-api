<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\TripEventPublisherInterface;
use App\Events\TripCreatedEventData;

final class FakeTripEventPublisher implements TripEventPublisherInterface
{
    /**
     * @var array<int, TripCreatedEventData>
     */
    private array $publishedEvents = [];

    public function publishTripCreated(
        TripCreatedEventData $event
    ): void {
        $this->publishedEvents[] = $event;
    }

    /**
     * @return array<int, TripCreatedEventData>
     */
    public function publishedEvents(): array
    {
        return $this->publishedEvents;
    }
}
