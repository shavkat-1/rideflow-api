<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function update(User $user, Trip $trip): bool
    {
        return $user->role === UserRole::Driver // Пользователь должен быть водителем.
            && $trip->driver_id === $user->id;  // Эта поездка должна быть назначена именно ему.
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $trip->passenger_id === $user->id;  // Удалить поездку может только пассажир, который её создал.
    }
}
