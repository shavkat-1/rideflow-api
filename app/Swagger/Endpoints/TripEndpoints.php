<?php

declare(strict_types=1);

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

final class TripEndpoints
{
    #[OA\Get(
        path: '/api/trips',
        summary: 'Получить список поездок',
        tags: ['Trips'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список поездок успешно получен',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                ref: '#/components/schemas/Trip'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Внутренняя ошибка сервера'
            ),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/trips/{id}',
        summary: 'Получить одну поездку',
        tags: ['Trips'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID поездки',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Поездка успешно получена',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/Trip'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Поездка не найдена'
            ),
            new OA\Response(
                response: 500,
                description: 'Внутренняя ошибка сервера'
            ),
        ]
    )]
    public function show(): void {}

    #[OA\Post(
        path: '/api/trips',
        summary: 'Создать поездку',
        tags: ['Trips'],

        requestBody: new OA\RequestBody(
            required: true,
            description: 'Данные для создания поездки',

            content: new OA\JsonContent(
                type: 'object',

                required: [
                    'passenger_id',
                    'pricing_type',
                    'estimated_price',
                ],

                properties: [
                    new OA\Property(
                        property: 'passenger_id',
                        type: 'integer',
                        example: 1
                    ),

                    new OA\Property(
                        property: 'pricing_type',
                        type: 'string',
                        enum: ['fixed', 'calculated'],
                        example: 'calculated'
                    ),

                    new OA\Property(
                        property: 'estimated_price',
                        type: 'number',
                        format: 'float',
                        minimum: 0,
                        example: 998.00
                    ),
                ]
            )
        ),

        responses: [
            new OA\Response(
                response: 201,
                description: 'Поездка успешно создана',

                content: new OA\JsonContent(
                    type: 'object',

                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/Trip'
                        ),
                    ]
                )
            ),

            new OA\Response(
                response: 422,
                description: 'Ошибка валидации'
            ),

            new OA\Response(
                response: 500,
                description: 'Внутренняя ошибка сервера'
            ),
        ]
    )]
    public function store(): void {}
}
