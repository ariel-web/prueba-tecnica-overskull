<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ProductStoreRequest', properties: [
    new OA\Property(property: 'name', type: 'string', example: 'Laptop Pro'),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'price', type: 'number', minimum: 0, example: 99.99),
    new OA\Property(property: 'stock', type: 'integer', minimum: 0, example: 50),
    new OA\Property(property: 'category_id', type: 'integer', example: 1),
    new OA\Property(property: 'status', type: 'boolean', example: true),
])]
class ProductStoreRequestSchema {}
