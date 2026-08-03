<?php

declare(strict_types=1);

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Trip',
    title: 'Trip',
    description: 'Данные поездки'
)]
final class TripSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'passenger_id', type: 'integer', example: 1)]
    public int $passenger_id;

    #[OA\Property(
        property: 'driver_id',
        type: 'integer',
        nullable: true,
        example: null
    )]
    public ?int $driver_id;

    #[OA\Property(
        property: 'status',
        type: 'string',
        enum: ['pending', 'in_progress', 'completed', 'cancelled'],
        example: 'pending'
    )]
    public string $status;

    #[OA\Property(
        property: 'pricing_type',
        type: 'string',
        enum: ['fixed', 'calculated'],
        example: 'calculated'
    )]
    public string $pricing_type;

    #[OA\Property(
        property: 'estimated_price',
        type: 'string',
        example: '998.00'
    )]
    public string $estimated_price;

    #[OA\Property(
        property: 'final_price',
        type: 'string',
        nullable: true,
        example: null
    )]
    public ?string $final_price;

    #[OA\Property(
        property: 'created_at',
        type: 'string',
        format: 'date-time',
        example: '2026-07-17T06:51:18.000000Z'
    )]
    public string $created_at;

    #[OA\Property(
        property: 'updated_at',
        type: 'string',
        format: 'date-time',
        example: '2026-07-17T06:51:18.000000Z'
    )]
    public string $updated_at;
}
