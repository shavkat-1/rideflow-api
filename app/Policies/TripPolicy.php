<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Trip;
use App\Models\User;

final class TripPolicy
{
    public function view(User $user, Trip $trip): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Passenger) {
            return $trip->passenger_id === $user->id;
        }

        if ($user->role === UserRole::Driver) {
            return $trip->driver_id === $user->id;
        }

        return false;
    }

    public function update(User $user, Trip $trip): bool
    {
        return $user->role === UserRole::Passenger
            && $trip->passenger_id === $user->id
            && $trip->status === 'pending';
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $user->role === UserRole::Passenger
            && $trip->passenger_id === $user->id
            && $trip->status === 'pending';
    }
}
