<?php

declare(strict_types=1);

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

final class AuthEndpoints
{
    #[OA\Post(
        path: '/api/register',
        summary: 'Зарегистрировать пассажира',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'name',
                    'email',
                    'password',
                    'password_confirmation',
                ],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        maxLength: 255,
                        example: 'Test Passenger'
                    ),
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        example: 'passenger@example.com'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        minLength: 8,
                        example: 'password123'
                    ),
                    new OA\Property(
                        property: 'password_confirmation',
                        type: 'string',
                        format: 'password',
                        example: 'password123'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Пользователь зарегистрирован',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Пользователь зарегистрирован'
                        ),
                        new OA\Property(
                            property: 'access_token',
                            type: 'string'
                        ),
                        new OA\Property(
                            property: 'token_type',
                            type: 'string',
                            example: 'Bearer'
                        ),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'id',
                                    type: 'integer',
                                    example: 22
                                ),
                                new OA\Property(
                                    property: 'name',
                                    type: 'string',
                                    example: 'Test Passenger'
                                ),
                                new OA\Property(
                                    property: 'email',
                                    type: 'string',
                                    format: 'email',
                                    example: 'passenger@example.com'
                                ),
                                new OA\Property(
                                    property: 'role',
                                    type: 'string',
                                    example: 'passenger'
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибка валидации'
            ),
        ]
    )]
    public function register(): void {}

    #[OA\Post(
        path: '/api/login',
        summary: 'Войти в систему',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        example: 'driver@example.com'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        example: 'password123'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешная авторизация',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'access_token',
                            type: 'string'
                        ),
                        new OA\Property(
                            property: 'token_type',
                            type: 'string',
                            example: 'Bearer'
                        ),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'id',
                                    type: 'integer',
                                    example: 21
                                ),
                                new OA\Property(
                                    property: 'name',
                                    type: 'string',
                                    example: 'Driver Test'
                                ),
                                new OA\Property(
                                    property: 'email',
                                    type: 'string',
                                    format: 'email',
                                    example: 'driver@example.com'
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Неверный email или пароль'
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибка валидации'
            ),
        ]
    )]
    public function login(): void {}

    #[OA\Get(
        path: '/api/me',
        summary: 'Получить профиль текущего пользователя',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Профиль пользователя',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'id',
                                    type: 'integer',
                                    example: 22
                                ),
                                new OA\Property(
                                    property: 'name',
                                    type: 'string',
                                    example: 'Test Passenger'
                                ),
                                new OA\Property(
                                    property: 'email',
                                    type: 'string',
                                    format: 'email',
                                    example: 'passenger@example.com'
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован'
            ),
        ]
    )]
    public function me(): void {}
}
