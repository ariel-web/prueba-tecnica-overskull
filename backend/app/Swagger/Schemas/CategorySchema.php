<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Category', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Categoría de electrónica'),
    new OA\Property(property: 'status', type: 'boolean', example: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
])]
class CategorySchema {}
