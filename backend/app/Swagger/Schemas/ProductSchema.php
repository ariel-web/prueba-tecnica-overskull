<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Product', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'name', type: 'string', example: 'Laptop Pro'),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
    new OA\Property(property: 'stock', type: 'integer', example: 50),
    new OA\Property(property: 'status', type: 'boolean', example: true),
    new OA\Property(property: 'category', ref: '#/components/schemas/Category', nullable: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
])]
class ProductSchema {}
