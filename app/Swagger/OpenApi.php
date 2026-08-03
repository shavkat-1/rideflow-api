<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'RideFlow API',
    description: 'API для управления поездками'
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Локальный сервер'
)]
final class OpenApi {}
