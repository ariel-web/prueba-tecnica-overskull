<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'User', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'name', type: 'string', example: 'Admin'),
    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@legacy.test'),
])]
class UserSchema {}

#[OA\Schema(schema: 'Category', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 1),
    new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Categoría de electrónica'),
    new OA\Property(property: 'status', type: 'boolean', example: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
])]
class CategorySchema {}

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

#[OA\Schema(schema: 'ProductStoreRequest', properties: [
    new OA\Property(property: 'name', type: 'string', example: 'Laptop Pro'),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'price', type: 'number', minimum: 0, example: 99.99),
    new OA\Property(property: 'stock', type: 'integer', minimum: 0, example: 50),
    new OA\Property(property: 'category_id', type: 'integer', example: 1),
    new OA\Property(property: 'status', type: 'boolean', example: true),
])]
class ProductStoreRequestSchema {}

#[OA\Schema(schema: 'ProductUpdateRequest', properties: [
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'price', type: 'number', minimum: 0),
    new OA\Property(property: 'stock', type: 'integer', minimum: 0),
    new OA\Property(property: 'category_id', type: 'integer'),
    new OA\Property(property: 'status', type: 'boolean'),
])]
class ProductUpdateRequestSchema {}

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

#[OA\Schema(schema: 'Pagination', properties: [
    new OA\Property(property: 'current_page', type: 'integer', example: 1),
    new OA\Property(property: 'last_page', type: 'integer', example: 5),
    new OA\Property(property: 'per_page', type: 'integer', example: 15),
    new OA\Property(property: 'total', type: 'integer', example: 75),
])]
class PaginationSchema {}
