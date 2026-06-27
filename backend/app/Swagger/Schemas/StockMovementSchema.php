<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'StockMovement', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'product_id', type: 'integer', example: 1),
    new OA\Property(property: 'type', type: 'string', enum: ['entrada', 'salida'], example: 'entrada'),
    new OA\Property(property: 'quantity', type: 'integer', example: 10),
    new OA\Property(property: 'reason', type: 'string', nullable: true),
    new OA\Property(property: 'user', type: 'object', properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
    ]),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
])]
class StockMovementSchema {}
