<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\StockMovementRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly StockMovementService $stockMovementService
    ) {}

    #[OA\Get(
        path: '/products',
        summary: 'Listar productos con filtros y paginación',
        tags: ['Products'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Buscar por nombre', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'min_price', in: 'query', required: false, schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', in: 'query', required: false, schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'min_stock', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'max_stock', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['price', 'stock', 'created_at'])),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/Pagination'),
                ]
            )),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list($request->only([
            'q', 'category_id', 'status', 'min_price', 'max_price',
            'min_stock', 'max_stock', 'sort', 'order', 'per_page',
        ]));

        return ProductResource::collection($products)
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Post(
        path: '/products',
        summary: 'Crear producto',
        tags: ['Products'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProductStoreRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Producto creado', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/products/{product}',
        summary: 'Ver producto',
        tags: ['Products'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Producto', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function show(Product $product): JsonResponse
    {
        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Put(
        path: '/products/{product}',
        summary: 'Actualizar producto',
        tags: ['Products'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Producto actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update($product, $request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/products/{product}',
        summary: 'Eliminar producto',
        tags: ['Products'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Eliminado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json(null, 204);
    }

    #[OA\Get(
        path: '/products/{product}/stock-movements',
        summary: 'Listar movimientos de stock de un producto',
        tags: ['Stock'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StockMovement')),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/Pagination'),
                ]
            )),
        ]
    )]
    public function stockMovements(Product $product): JsonResponse
    {
        $movements = $this->stockMovementService->listByProduct($product->id);

        return StockMovementResource::collection($movements)
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Post(
        path: '/products/{product}/stock-movements',
        summary: 'Registrar movimiento de stock',
        tags: ['Stock'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['entrada', 'salida'], example: 'entrada'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 10),
                    new OA\Property(property: 'reason', type: 'string', example: 'Restock', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Movimiento creado', content: new OA\JsonContent(ref: '#/components/schemas/StockMovement')),
            new OA\Response(response: 422, description: 'Stock insuficiente o error de validación'),
        ]
    )]
    public function storeStockMovement(StockMovementRequest $request, Product $product): JsonResponse
    {
        $movement = $this->stockMovementService->create(
            $product->id,
            $request->validated(),
            $request->user()->id
        );

        return (new StockMovementResource($movement))
            ->response()
            ->setStatusCode(201);
    }
}
