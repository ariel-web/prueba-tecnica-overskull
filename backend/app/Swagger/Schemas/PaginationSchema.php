<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Pagination', properties: [
    new OA\Property(property: 'current_page', type: 'integer', example: 1),
    new OA\Property(property: 'last_page', type: 'integer', example: 5),
    new OA\Property(property: 'per_page', type: 'integer', example: 15),
    new OA\Property(property: 'total', type: 'integer', example: 75),
])]
class PaginationSchema {}
