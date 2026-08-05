<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Events\TripCreatedEventData;

interface TripEventPublisherInterface
{
    public function publishTripCreated(
        TripCreatedEventData $event
    ): void;
}
