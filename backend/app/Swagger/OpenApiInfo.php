<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    security: [['sanctum' => []]]
)]
#[OA\Info(
    title: 'Sistema de Gestión de Productos API',
    description: 'API REST para gestión de productos, categorías y movimientos de stock.',
    version: '1.0.0',
    contact: new OA\Contact(email: 'admin@example.com'),
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Bearer token de Sanctum',
)]
#[OA\Server(url: 'http://localhost:8080/api', description: 'Servidor local')]
class OpenApiInfo
{
}
