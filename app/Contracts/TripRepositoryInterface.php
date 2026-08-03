<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

interface TripRepositoryInterface
{
    public function getAll(): Collection;

    public function findById(int $id): ?Trip;

    public function create(array $data): Trip;

    public function update(int $id, array $data): Trip;

    public function delete(int $id): bool;
}
