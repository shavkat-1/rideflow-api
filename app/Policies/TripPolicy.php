<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

final class TripPolicy
{
    public function update(User $user, Trip $trip): bool
    {
        return $trip->passenger_id === $user->id
            && $trip->status === 'pending';
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $trip->passenger_id === $user->id
            && $trip->status === 'pending';
    }
}
