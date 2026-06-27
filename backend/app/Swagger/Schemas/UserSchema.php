<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'User', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'name', type: 'string', example: 'Admin'),
    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@legacy.test'),
])]
class UserSchema {}
