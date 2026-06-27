<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ProductUpdateRequest', properties: [
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'price', type: 'number', minimum: 0),
    new OA\Property(property: 'stock', type: 'integer', minimum: 0),
    new OA\Property(property: 'category_id', type: 'integer'),
    new OA\Property(property: 'status', type: 'boolean'),
])]
class ProductUpdateRequestSchema {}
