<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    #[OA\Get(
        path: '/categories',
        summary: 'Listar categorías con paginación',
        tags: ['Categories'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Buscar por nombre', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/Pagination'),
                ]
            )),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->list($request->only(['q', 'status', 'per_page']));

        return CategoryResource::collection($categories)
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Post(
        path: '/categories',
        summary: 'Crear categoría',
        tags: ['Categories'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Categoría creada', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/categories/{category}',
        summary: 'Ver categoría',
        tags: ['Categories'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Categoría', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function show(Category $category): JsonResponse
    {
        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Put(
        path: '/categories/{category}',
        summary: 'Actualizar categoría',
        tags: ['Categories'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Categoría actualizada', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category = $this->categoryService->update($category, $request->validated());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(200);
    }

    #[OA\Delete(
        path: '/categories/{category}',
        summary: 'Eliminar categoría',
        tags: ['Categories'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Eliminada'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return response()->json(null, 204);
    }

    public function sync(): JsonResponse
    {
        return response()->json($this->categoryService->sync());
    }
}
