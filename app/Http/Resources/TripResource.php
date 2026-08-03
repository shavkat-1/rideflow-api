<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Создаем свой toArray();  $this = конкретный объект Trip, который Resource сейчас преобразует
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'passenger_id' => $this->passenger_id,
            'driver_id' => $this->driver_id,
            'status' => $this->status,
            'pricing_type' => $this->pricing_type,
            'estimated_price' => $this->estimated_price,
            'final_price' => $this->final_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
