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
                            property: 'message',
                            type: 'string',
                            example: 'Поездка создана. Подтверждение будет отправлено позже.'
                        ),
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

    #[OA\Patch(
        path: '/api/trips/{trip}',
        summary: 'Обновить ожидающую поездку',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Trips'],
        parameters: [
            new OA\Parameter(
                name: 'trip',
                description: 'ID поездки',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 26
                )
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'pricing_type',
                        type: 'string',
                        enum: ['fixed', 'calculated'],
                        example: 'fixed'
                    ),
                    new OA\Property(
                        property: 'estimated_price',
                        type: 'number',
                        format: 'float',
                        minimum: 0,
                        example: 180
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Поездка обновлена',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/Trip'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован'
            ),
            new OA\Response(
                response: 403,
                description: 'Нет прав на обновление поездки'
            ),
            new OA\Response(
                response: 404,
                description: 'Поездка не найдена'
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибка валидации'
            ),
        ]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/api/trips/{trip}',
        summary: 'Удалить ожидающую поездку',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Trips'],
        parameters: [
            new OA\Parameter(
                name: 'trip',
                description: 'ID поездки',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 26
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Поездка удалена',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Поездка удалена'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован'
            ),
            new OA\Response(
                response: 403,
                description: 'Нет прав на удаление поездки'
            ),
            new OA\Response(
                response: 404,
                description: 'Поездка не найдена'
            ),
        ]
    )]
    public function destroy(): void {}

    #[OA\Patch(
        path: '/api/trips/{id}/accept',
        summary: 'Принять поездку водителем',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Trips'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID поездки',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 24
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Поездка принята водителем',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/Trip'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован'
            ),
            new OA\Response(
                response: 403,
                description: 'Доступ разрешён только водителям'
            ),
            new OA\Response(
                response: 404,
                description: 'Поездка не найдена'
            ),
            new OA\Response(
                response: 409,
                description: 'Поездка уже недоступна для принятия'
            ),
        ]
    )]
    public function accept(): void {}
}
