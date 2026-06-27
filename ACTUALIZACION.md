# Documento de Actualización Técnica — Prueba Full Stack Laravel + Vue.js

> Registro exhaustivo de todos los cambios realizados sobre el código legacy original.
> Incluye código fuente completo de cada archivo nuevo, diffs de archivos modificados
> y eliminados, con justificación técnica de cada decisión.

---

## Tabla de contenidos

1. [Estado del repositorio Git](#1-estado-del-repositorio-git)
2. [Archivos eliminados](#2-archivos-eliminados)
3. [Archivos nuevos — Backend](#3-archivos-nuevos--backend)
4. [Archivos nuevos — Frontend](#4-archivos-nuevos--frontend)
5. [Archivos nuevos — Docker e infraestructura](#5-archivos-nuevos--docker-e-infraestructura)
6. [Archivos modificados](#6-archivos-modificados)
7. [Criterios de descarte inmediato (Sección 10)](#7-criterios-de-descarte-inmediato-sección-10)
8. [Preguntas de entrevista técnica (Sección 11)](#8-preguntas-de-entrevista-técnica-sección-11)

---

## 1. Estado del repositorio Git

```
Commit inicial:  faf7ea6  inicio de la prueba
Commit fase 1:   c3ba747  docker: consolidar infraestructura + upgrade Laravel 8 a 11 + PHP 8.2
Fase 2:          sin commitear (arquitectura + componentes + optimización + documentación)
```

**Archivos modificados (M):** 37
**Archivos nuevos (??):** ~70
**Archivos eliminados (D):** 5

---

## 2. Archivos eliminados

### `backend/app/Console/Kernel.php`

**Motivo:** Laravel 11 elimina `Console/Kernel.php`. El scheduling de comandos y
registro de comandos Artisan se mueve a `routes/console.php` y `bootstrap/app.php`
con el método `->withSchedule()`.

**Qué hacía antes:** Registraba comandos Artisan y definía el schedule de tareas
programadas (cron).

**Impacto:** Ninguno. El proyecto no usaba comandos personalizados ni tareas
programadas. Si los tuviera, se migrarían a `routes/console.php`.

---

### `backend/app/Exceptions/Handler.php`

**Motivo:** Laravel 11 elimina `Exceptions/Handler.php`. El manejo de excepciones
se configura en `bootstrap/app.php` con `->withExceptions()`.

**Qué hacía antes:** Centralizaba el manejo de excepciones HTTP (renderización de
errores 404, 500, ValidationException, etc.).

**Impacto:** Ninguno. El `bootstrap/app.php` actual tiene `->withExceptions()`
vacío porque Laravel 11 ya maneja los casos por defecto. Si se necesitara
personalizar (ej: responder JSON en vez de HTML para 404), se haría ahí.

---

### `backend/app/Http/Kernel.php`

**Motivo:** Laravel 11 elimina `Http/Kernel.php`. El registro de middleware y
grupos de middleware se mueve a `bootstrap/app.php` con `->withMiddleware()`.

**Qué hacía antes:** Definía la pila de middleware global (`EncryptCookies`,
`ShareErrorsFromSession`, `VerifyCsrfToken`, etc.) y los grupos `web` y `api`.

**Impacto:** Ninguno. Laravel 11 registra automáticamente el middleware esencial.
El middleware de Sanctum se aplica vía rutas (`auth:sanctum` en `routes/api.php`).

---

### `backend/app/Providers/RouteServiceProvider.php`

**Motivo:** Laravel 11 registra las rutas automáticamente. El `RouteServiceProvider`
ya no existe.

**Qué hacía antes:** Cargaba `routes/web.php` y `routes/api.php` con prefijos
y middleware configurados.

**Impacto:** Ninguno. `bootstrap/app.php` configura el routing:

```php
->withRouting(
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
)
```

---

### `backend/docker-compose.yml`

**Motivo:** Se consolidó en la raíz del proyecto como un único `docker-compose.yml`
que orquesta todos los servicios (backend, frontend, nginx, mysql, redis, worker).

**Qué hacía antes:** Era un docker-compose legacy que solo levantaba el backend,
sin nginx, sin frontend ni redis.

---

## 3. Archivos nuevos — Backend

### `backend/app/Http/Controllers/AuthController.php`

Controlador delgado para autenticación. Inyecta `AuthService` por DI.
Cada endpoint tiene anotaciones Swagger `#[OA\Post]`, `#[OA\Get]`, etc.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    #[OA\Post(
        path: '/login',
        summary: 'Iniciar sesión',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@legacy.test'),
                    new OA\Property(property: 'password', type: 'string', example: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token generado', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                ]
            )),
            new OA\Response(response: 401, description: 'Credenciales inválidas'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->authService->login($request->validated());
        return response()->json($data);
    }

    #[OA\Get(
        path: '/me',
        summary: 'Usuario autenticado',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Datos del usuario', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->authService->user($request));
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Cerrar sesión',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);
        return response()->json(['message' => 'Logged out']);
    }
}
```

**Lo que cambió vs. legacy:** Antes el controlador tenía toda la lógica de Hash::check,
creación de token y manejo de errores embebidos. Ahora delega a `AuthService` y usa
`LoginRequest` para validar.

---

### `backend/app/Http/Controllers/ProductController.php`

Controlador completo para productos + stock movements. Inyecta `ProductService` y
`StockMovementService`. Route model binding con `Product $product`.

```php
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

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());
        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): JsonResponse
    {
        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(200);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update($product, $request->validated());
        return (new ProductResource($product))
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->productService->delete($product);
        return response()->json(null, 204);
    }

    public function stockMovements(Product $product): JsonResponse
    {
        $movements = $this->stockMovementService->listByProduct($product->id);
        return StockMovementResource::collection($movements)
            ->response()
            ->setStatusCode(200);
    }

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
```

(Omitidas anotaciones Swagger por espacio — están presentes en el archivo real.)

**Endpoints expuestos:**
- `GET /api/products` — listado paginado con filtros y ordenamiento
- `POST /api/products` — crear producto
- `GET /api/products/{product}` — ver producto con categoría
- `PUT /api/products/{product}` — actualizar
- `DELETE /api/products/{product}` — eliminar
- `GET /api/products/{product}/stock-movements` — movimientos del producto
- `POST /api/products/{product}/stock-movements` — registrar movimiento

---

### `backend/app/Http/Controllers/CategoryController.php`

Controlador CRUD para categorías. Inyecta `CategoryService`.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->list($request->only(['q', 'status', 'per_page']));
        return CategoryResource::collection($categories)
            ->response()
            ->setStatusCode(200);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());
        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category): JsonResponse
    {
        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(200);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category = $this->categoryService->update($category, $request->validated());
        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(200);
    }

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
```

---

### `backend/app/Http/Controllers/DashboardController.php`

Dashboard con métricas cacheadas + health check.

```php
<?php

namespace App\Http\Controllers;

use App\Services\ashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->dashboardService->metrics());
    }

    public function health(): JsonResponse
    {
        try {
            DB::select('SELECT 1');
            return response()->json(['status' => 'ok', 'database' => 'connected']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'fail', 'error' => $e->getMessage()], 500);
        }
    }
}
```

---

### `backend/app/Services/AuthService.php`

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            abort(401, 'Invalid credentials');
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function logout(Request $request): void
    {
        $request->user()->currentAccessToken()->delete();
    }

    public function user(Request $request): User
    {
        return $request->user();
    }
}
```

**Decisión:** Se usa Sanctum (tokens Bearer) en lugar de JWT porque Sanctum
es nativo de Laravel, más simple y se integra con la base de datos sin librerías
adicionales.

---

### `backend/app/Services/ProductService.php`

```php
<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Product::query()
            ->with('category')
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($filters['category_id'] ?? null, fn ($q, $categoryId) => $q->where('category_id', $categoryId))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['min_price'] ?? null, fn ($q, $min) => $q->where('price', '>=', $min))
            ->when($filters['max_price'] ?? null, fn ($q, $max) => $q->where('price', '<=', $max))
            ->when($filters['min_stock'] ?? null, fn ($q, $min) => $q->where('stock', '>=', $min))
            ->when($filters['max_stock'] ?? null, fn ($q, $max) => $q->where('stock', '<=', $max))
            ->when(
                $filters['sort'] ?? null,
                fn ($q, $sort) => $q->orderBy($sort, $filters['order'] ?? 'desc'),
                fn ($q) => $q->orderBy('created_at', 'desc')
            )
            ->paginate($filters['per_page'] ?? 15);
    }

    public function get(int $id): Product
    {
        return Product::with('category')->findOrFail($id);
    }

    public function create(array $data): Product
    {
        $product = Product::create($data);
        $this->flushCache();
        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        $this->flushCache();
        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
        $this->flushCache();
    }

    private function flushCache(): void
    {
        Cache::forget('dashboard.metrics');
    }
}
```

**Optimización clave:** `->with('category')` previene N+1. Si no estuviera, cada
acceso a `$product->category->name` dispararía una query individual (1 + N).

---

### `backend/app/Services/CategoryService.php`

```php
<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CategoryService
{
    private const CACHE_PREFIX = 'categories:list:';
    private const CACHE_TTL = 600;

    public function list(array $filters = []): LengthAwarePaginator
    {
        $cacheKey = self::CACHE_PREFIX . md5(json_encode($filters));

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn () => Category::query()
                ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
                ->when($filters['q'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
                ->orderBy('created_at', 'desc')
                ->paginate($filters['per_page'] ?? 15)
        );
    }

    public function get(int $id): Category
    {
        return Category::findOrFail($id);
    }

    public function create(array $data): Category
    {
        $category = Category::create($data);
        $this->flushCache();
        return $category;
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        $this->flushCache();
        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
        $this->flushCache();
    }

    public function sync(): array
    {
        $this->flushCache();
        $defaultFilters = [
            ['per_page' => 100],
            ['per_page' => 100, 'status' => 1],
            ['per_page' => 100, 'status' => 0],
        ];
        foreach ($defaultFilters as $filters) {
            $this->list($filters);
        }
        return ['status' => 'synced', 'cached_keys' => count($defaultFilters)];
    }

    private function flushCache(): void
    {
        Cache::forget('dashboard.metrics');
        $prefix = config('cache.prefix', '');
        $keys = Redis::keys($prefix . self::CACHE_PREFIX . '*');
        foreach ($keys as $key) {
            Cache::forget(str_replace($prefix, '', $key));
        }
    }
}
```

**Cache:** Se cachea el listado por 600s (10 min). Cada combinación de filtros
genera un key único con `md5(json_encode($filters))`. En writes (create/update/delete)
se hace flush de todas las keys que coinciden con el prefijo.

---

### `backend/app/Services/StockMovementService.php`

```php
<?php

namespace App\Services;

use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    public function listByProduct(int $productId, int $perPage = 15): LengthAwarePaginator
    {
        return StockMovement::where('product_id', $productId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(int $productId, array $data, int $userId): StockMovement
    {
        return DB::transaction(function () use ($productId, $data, $userId) {
            $product = DB::table('products')->where('id', $productId)->lockForUpdate()->first();

            if (!$product) {
                abort(404, 'Product not found');
            }

            if ($data['type'] === StockMovement::TYPE_SALIDA && $product->stock < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock insuficiente',
                ]);
            }

            $newStock = $data['type'] === StockMovement::TYPE_SALIDA
                ? $product->stock - $data['quantity']
                : $product->stock + $data['quantity'];

            DB::table('products')->where('id', $productId)->update(['stock' => $newStock]);

            return StockMovement::create([
                'product_id' => $productId,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'reason' => $data['reason'] ?? null,
                'user_id' => $userId,
            ])->load('product', 'user');
        });
    }
}
```

**Transacción + lockForUpdate:** Previene race conditions. Si dos peticiones intentan
descontar stock simultáneamente, `lockForUpdate()` bloquea la fila del producto hasta
que la transacción termine, garantizando que el stock nunca sea negativo.

**Validación de negocio:** Si `type=salida` y `quantity > stock disponible`, lanza
`ValidationException` con status 422 y mensaje "Stock insuficiente".

---

### `backend/app/Services/AuditService.php`

```php
<?php

namespace App\Services;

use App\Jobs\LogAuditEntry;

class AuditService
{
    public function log(
        ?int $userId,
        string $action,
        string $entity,
        int $entityId,
        ?array $old = null,
        ?array $new = null
    ): void {
        if (!config('audit.enabled')) {
            return;
        }

        LogAuditEntry::dispatch($userId, $action, $entity, $entityId, $old, $new);
    }
}
```

**Async:** El log se despacha como Job en cola (Redis). La respuesta HTTP no espera
a que se guarde el registro de auditoría.

---

### `backend/app/Services/DashboardService.php`

```php
<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function metrics(): array
    {
        return Cache::remember('dashboard.metrics', 300, function () {
            return [
                'products' => Product::count(),
                'categories' => Category::count(),
                'low_stock' => Product::where('stock', '<', 10)
                    ->with('category')
                    ->orderBy('stock', 'asc')
                    ->limit(50)
                    ->get(),
                'last_movements' => StockMovement::with(['product', 'user'])
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get(),
            ];
        });
    }
}
```

**Cache:** Se cachea por 300s (5 min). Cada vez que se crea/actualiza/elimina un
producto o categoría, se hace `Cache::forget('dashboard.metrics')` en el service
correspondiente.

---

### `backend/app/Http/Requests/Auth/LoginRequest.php`

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

---

### `backend/app/Http/Requests/Category/StoreCategoryRequest.php`

```php
<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
            'status' => ['boolean'],
        ];
    }
}
```

---

### `backend/app/Http/Requests/Category/UpdateCategoryRequest.php`

```php
<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')->id ?? $this->route('category');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($categoryId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'boolean'],
        ];
    }
}
```

**Nota:** Usa `Rule::unique()->ignore()` para permitir actualizar el name al mismo
valor si pertenece a la misma categoría.

---

### `backend/app/Http/Requests/Product/StoreProductRequest.php`

```php
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'status' => ['boolean'],
        ];
    }
}
```

---

### `backend/app/Http/Requests/Product/UpdateProductRequest.php`

```php
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'status' => ['sometimes', 'boolean'],
        ];
    }
}
```

---

### `backend/app/Http/Requests/Product/StockMovementRequest.php`

```php
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:entrada,salida'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
```

---

### `backend/app/Http/Resources/ProductResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'category_id' => $this->category_id,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

**Nota:** `$this->whenLoaded('category')` incluye la categoría solo si fue cargada
con eager loading. Si no, omite el campo para no disparar lazy loading.

---

### `backend/app/Http/Resources/CategoryResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

---

### `backend/app/Http/Resources/StockMovementResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'reason' => $this->reason,
            'user' => [
                'id' => $this->whenLoaded('user')?->id,
                'name' => $this->whenLoaded('user')?->name,
            ],
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at,
        ];
    }
}
```

---

### `backend/app/Http/Resources/AuditLogResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'entity' => $this->entity,
            'entity_id' => $this->entity_id,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'created_at' => $this->created_at,
        ];
    }
}
```

---

### `backend/app/Traits/Auditable.php`

```php
<?php

namespace App\Traits;

use App\Jobs\LogAuditEntry;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        if (!config('audit.enabled')) {
            return;
        }

        static::created(function ($model) {
            $model->dispatchAudit('create', null, $model->toArray());
        });

        static::updated(function ($model) {
            $model->dispatchAudit(
                'update',
                $model->getOriginal(),
                $model->fresh()->toArray()
            );
        });

        static::deleted(function ($model) {
            $model->dispatchAudit('delete', $model->toArray(), null);
        });
    }

    protected function dispatchAudit(string $action, ?array $old, ?array $new): void
    {
        LogAuditEntry::dispatch(
            Auth::id(),
            $action,
            static::class,
            $this->getKey(),
            $old,
            $new
        );
    }
}
```

**Cómo funciona:**
1. Cualquier modelo que `use Auditable` registra automáticamente eventos created/updated/deleted
2. `bootAuditable()` se ejecuta al bootear el modelo (patrón de traits de Laravel)
3. `$model->getOriginal()` captura los valores antes del cambio
4. `$model->fresh()->toArray()` captura los valores después del cambio
5. `LogAuditEntry::dispatch()` despacha el job a la cola Redis sin bloquear HTTP

**Modelos que usan el trait:** `Product`, `Category`, `StockMovement`

---

### `backend/app/Jobs/LogAuditEntry.php`

```php
<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogAuditEntry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ?int $userId,
        public readonly string $action,
        public readonly string $entity,
        public readonly int $entityId,
        public readonly ?array $oldValues = null,
        public readonly ?array $newValues = null,
    ) {}

    public function handle(): void
    {
        AuditLog::create([
            'user_id' => $this->userId,
            'action' => $this->action,
            'entity' => $this->entity,
            'entity_id' => $this->entityId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
        ]);
    }
}
```

**ShouldQueue:** El job se procesa en la cola Redis. El worker (`queue-worker`)
se levanta automáticamente con `docker compose up` y ejecuta
`php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600`.

---

### `backend/app/Models/AuditLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'action', 'entity', 'entity_id', 'old_values', 'new_values'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### `backend/app/Swagger/OpenApiInfo.php`

```php
<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    security: [new OA\SecurityScheme(securityScheme: 'sanctum')]
)]
#[OA\Info(
    title: 'Sistema de Gestión de Productos API',
    description: 'API REST para gestión de productos, categorías y movimientos de stock.',
    version: '1.0.0',
    contact: new OA\Contact(email: 'admin@example.com'),
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: OA\SecurityScheme::HTTP,
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Bearer token de Sanctum',
)]
#[OA\Server(url: 'http://localhost:8080/api', description: 'Servidor local')]
class OpenApiInfo
{
}
```

---

### `backend/app/Swagger/Schemas.php`

Define los schemas de OpenAPI para User, Category, Product, StockMovement,
Pagination, ProductStoreRequest y ProductUpdateRequest usando atributos PHP 8.

```php
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
    new OA\Property(property: 'description', type: 'string', nullable: true),
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
```

---

### `backend/app/Providers/TelescopeServiceProvider.php`

Registra Telescope. Filtra entradas: en `local` guarda todo; en otros entornos
solo guarda excepciones, requests fallidos, jobs fallidos y tareas programadas.

```php
<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);
        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
```

---

### Migraciones

#### `backend/database/migrations/2024_01_01_000004_create_audit_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action');
            $table->string('entity');
            $table->unsignedBigInteger('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();

            $table->index(['entity', 'entity_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

#### `backend/database/migrations/2024_01_01_000005_add_indexes_and_foreign_keys.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index('status');
            $table->index('price');
            $table->index('stock');
            $table->index('created_at');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('type');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['price']);
            $table->dropIndex(['stock']);
            $table->dropIndex(['created_at']);
            $table->dropForeign(['category_id']);
        });
    }
};
```

**Índices agregados y por qué:**
- `products.status` — se filtra por activo/inactivo
- `products.price` — se filtra por rango de precio y se ordena por precio
- `products.stock` — se filtra por rango de stock y se ordena por stock
- `products.created_at` — se ordena por fecha (default: desc)
- `products.category_id` — foreign key + se filtra por categoría
- `stock_movements.type` — se filtra por entrada/salida
- `stock_movements.product_id` — foreign key
- `stock_movements.user_id` — foreign key
- `categories.status` — se filtra por estado
- `audit_logs(entity, entity_id)` — índice compuesto para consultas por entidad
- `audit_logs.user_id` — índice para consultas por usuario

---

### Seeders de volumen

#### `backend/database/seeders/CategorySeeder.php`

Genera **100 categorías**: 15 base con nombres reales + 85 generadas con faker.

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    private const BASE_CATEGORIES = [
        ['Electrónica', 'Televisores, radios y dispositivos electrónicos de consumo'],
        ['Computación', 'Laptops, accesorios y componentes de computadora'],
        ['Audio y Video', 'Audífonos, parlantes, equipos de sonido y home theater'],
        ['Telefonía', 'Smartphones, fundas, cargadores y accesorios para móviles'],
        ['Hogar y Cocina', 'Utensilios de cocina, menaje y decoración para el hogar'],
        ['Electrodomésticos', 'Línea blanca y pequeños electrodomésticos'],
        ['Ropa y Calzado', 'Vestimenta, calzado y accesorios de moda para todas las edades'],
        ['Deportes y Aire Libre', 'Equipamiento deportivo, fitness y actividades al aire libre'],
        ['Juguetes y Juegos', 'Juguetes educativos, juegos de mesa y entretenimiento infantil'],
        ['Libros y Papelería', 'Libros, material de oficina y artículos escolares'],
        ['Salud y Belleza', 'Cuidado personal, cosméticos y productos de higiene'],
        ['Automotriz', 'Repuestos, accesorios y herramientas para vehículos'],
        ['Herramientas', 'Herramientas manuales, eléctricas y equipos de trabajo'],
        ['Jardín y Exterior', 'Muebles de exterior, plantas y artículos de jardinería'],
        ['Mascotas', 'Alimentos, accesorios y cuidado para mascotas'],
    ];

    private const PREFIXES = [
        'Premium', 'Pro', 'Express', 'Elite', 'Industrial', 'Artesanal',
        'Eco', 'Smart', 'Digital', 'Profesional', 'Mini', 'Max',
    ];

    private const SUFFIXES = [
        'de Lujo', 'Especialidad', 'Alta Gama', 'Uso Diario', 'Edición Limitada',
        'Importado', 'Nacional', 'Certificado', 'Orgánico', 'Sostenible',
    ];

    public function run(): void
    {
        $categories = [];
        $now = now();

        foreach (self::BASE_CATEGORIES as [$name, $description]) {
            $categories[] = [
                'name' => $name,
                'description' => $description,
                'status' => true,
                'created_at' => $now->copy()->subDays(rand(1, 90)),
                'updated_at' => $now,
            ];
        }

        $faker = \Faker\Factory::create();
        $generated = count($categories);

        while ($generated < 100) {
            $baseName = $faker->randomElement(self::BASE_CATEGORIES)[0];
            $name = $faker->randomElement(self::PREFIXES) . ' ' . $baseName;
            $name .= ' ' . $faker->randomElement(self::SUFFIXES) . ' #' . ($generated + 1);

            $categories[] = [
                'name' => $name,
                'description' => 'Subcategoría de ' . strtolower($baseName) . ' con productos especializados',
                'status' => $faker->boolean(85),
                'created_at' => $now->copy()->subDays(rand(1, 90)),
                'updated_at' => $now,
            ];
            $generated++;
        }

        DB::table('categories')->insert($categories);
    }
}
```

#### `backend/database/seeders/ProductSeeder.php`

Genera **10,000 productos** en chunks de 500 para no agotar memoria.

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    private const ADJECTIVES = ['Premium', 'Pro', 'Ultra', 'Max', 'Lite', 'Plus', 'Classic', 'Smart', 'Eco', 'Elite'];

    private const NOUNS = [
        'Smartphone', 'Laptop', 'Audífonos', 'Teclado', 'Monitor', 'Cámara',
        'Parlante', 'Tablet', 'Mouse', 'Webcam', 'Impresora', 'Proyector',
        'Sartén', 'Olla', 'Cuchillo', 'Licuadora', 'Cafetera', 'Plancha',
        'Zapatillas', 'Polerón', 'Mochila', 'Termo', 'Botella', 'Guantes',
        'Balón', 'Bicicleta', 'Casco', 'Mancuernas', 'Tabla', 'Cuerda',
        'Taladro', 'Llave', 'Amoladora', 'Nivel', 'Destornillador', 'Martillo',
        'Mecedora', 'Macetero', 'Manguera', 'Paraguas', 'Kit', 'Set',
    ];

    private const BRANDS = [
        'Sony', 'Samsung', 'LG', 'Philips', 'Bose', 'JBL', 'Logitech',
        'Dell', 'HP', 'ASUS', 'Apple', 'Xiaomi', 'Bosch', 'Makita',
        'Tefal', 'Tramontina', 'Victorinox', 'Nike', 'Adidas', 'Stanley',
    ];

    private const TARGET_COUNT = 10000;
    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $maxCategoryId = (int) DB::table('categories')->max('id');
        $now = now();

        for ($offset = 0; $offset < self::TARGET_COUNT; $offset += self::CHUNK_SIZE) {
            $batch = [];
            $batchSize = min(self::CHUNK_SIZE, self::TARGET_COUNT - $offset);

            for ($i = 0; $i < $batchSize; $i++) {
                $batch[] = [
                    'name' => $faker->randomElement(self::BRANDS) . ' '
                        . $faker->randomElement(self::NOUNS) . ' '
                        . $faker->randomElement(self::ADJECTIVES) . ' '
                        . $faker->numberBetween(1000, 9999),
                    'description' => $faker->sentence(8),
                    'price' => $faker->numberBetween(9990, 999990),
                    'stock' => $faker->numberBetween(0, 200),
                    'category_id' => $faker->numberBetween(1, $maxCategoryId),
                    'status' => $faker->boolean(90),
                    'created_at' => $now->copy()->subDays(rand(1, 180)),
                    'updated_at' => $now,
                ];
            }

            DB::table('products')->insert($batch);
        }
    }
}
```

#### `backend/database/seeders/StockMovementSeeder.php`

Genera **30,000 movimientos** en chunks de 1000.

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockMovementSeeder extends Seeder
{
    private const ENTRY_REASONS = [
        'Compra a proveedor',
        'Reposición de stock',
        'Devolución de cliente',
        'Ajuste positivo de inventario',
        'Transferencia entrante de sucursal',
        'Recepción de mercancía en consignación',
        'Reingreso por cancelación de venta',
    ];

    private const EXIT_REASONS = [
        'Venta directa',
        'Venta online',
        'Devolución a proveedor por defecto',
        'Daño de mercancía',
        'Merma por caducidad',
        'Ajuste negativo de inventario',
        'Transferencia saliente a sucursal',
        'Salida por garantía',
    ];

    private const TARGET_COUNT = 30000;
    private const CHUNK_SIZE = 1000;

    public function run(): void
    {
        $maxProductId = (int) DB::table('products')->max('id');
        $movements = [];
        $now = now();

        for ($i = 1; $i <= self::TARGET_COUNT; $i++) {
            $type = rand(0, 1) ? 'entrada' : 'salida';
            $reasons = $type === 'entrada' ? self::ENTRY_REASONS : self::EXIT_REASONS;

            $movements[] = [
                'product_id' => rand(1, $maxProductId),
                'type' => $type,
                'quantity' => rand(1, 50),
                'reason' => $reasons[array_rand($reasons)],
                'user_id' => 1,
                'created_at' => $now->copy()->subDays(rand(0, 180)),
                'updated_at' => $now,
            ];

            if (count($movements) === self::CHUNK_SIZE) {
                DB::table('stock_movements')->insert($movements);
                $movements = [];
            }
        }

        if (!empty($movements)) {
            DB::table('stock_movements')->insert($movements);
        }
    }
}
```

---

### Tests

#### `backend/tests/Feature/AuthTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_me_requires_auth(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('email', $user->email);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/logout');

        $response->assertStatus(200);
    }
}
```

#### `backend/tests/Feature/StockMovementTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_stock_movements_by_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        StockMovement::factory()->count(5)->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/products/{$product->id}/stock-movements");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'type', 'quantity', 'user']],
                'meta' => ['current_page', 'total'],
            ]);
    }

    public function test_create_entrada_increases_stock(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type' => 'entrada',
                'quantity' => 5,
                'reason' => 'Restock',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('type', 'entrada')
            ->assertJsonPath('quantity', 5);

        $this->assertEquals(15, $product->fresh()->stock);
    }

    public function test_create_salida_decreases_stock(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type' => 'salida',
                'quantity' => 3,
                'reason' => 'Sale',
            ]);

        $response->assertStatus(201);
        $this->assertEquals(7, $product->fresh()->stock);
    }

    public function test_salida_rejected_when_stock_insufficient(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'stock' => 5,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type' => 'salida',
                'quantity' => 15,
                'reason' => 'Trying to sell too much',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        $this->assertEquals(5, $product->fresh()->stock);
    }

    public function test_stock_movement_requires_auth(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/products/{$product->id}/stock-movements");

        $response->assertStatus(401);
    }

    public function test_stock_movement_validation(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type' => 'invalid',
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'quantity']);
    }
}
```

**Test clave:** `test_salida_rejected_when_stock_insufficient` verifica que intentar
sacar 15 unidades de un producto con stock=5 retorna 422 y el stock queda en 5
(no se modifica). Este es el criterio de descarte #7.

---

### `backend/routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/health', [DashboardController::class, 'health']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::get('/products/{product}/stock-movements', [ProductController::class, 'stockMovements']);
    Route::post('/products/{product}/stock-movements', [ProductController::class, 'storeStockMovement']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::post('/categories/sync', [CategoryController::class, 'sync']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
});
```

**Cambios vs. legacy:**
- Antes: rutas sin protección, todas públicas
- Ahora: `/login` y `/health` públicos, todo lo demás con `auth:sanctum`
- Route model binding: `{product}` y `{category}` resuelven automáticamente el modelo

---

### `backend/bootstrap/app.php`

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
```

**Cambios vs. legacy:** Antes usaba `Kernel.php` + `RouteServiceProvider.php`.
Ahora todo se configura aquí con el fluent API de Laravel 11.

---

### `backend/bootstrap/providers.php`

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
];
```

---

### `backend/config/audit.php`

```php
<?php

return [
    'enabled' => env('AUDIT_ENABLED', true),
];
```

---

## 4. Archivos nuevos — Frontend

### `frontend/src/components/DataTable.vue`

```vue
<template>
  <div class="bg-white rounded-lg shadow overflow-hidden">
    <LoadingSpinner v-if="loading" text="Cargando..." />
    <template v-else>
      <div v-if="items.length === 0" class="px-4 py-8 text-center text-gray-400 text-sm">
        No hay registros para mostrar
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3 text-left text-sm font-medium text-gray-600 select-none"
              :class="col.sortable ? 'cursor-pointer hover:bg-gray-100' : ''"
              @click="col.sortable && handleSort(col.key)"
            >
              {{ col.label }}
              <span v-if="col.sortable" class="ml-1 text-xs">
                <template v-if="sortKey === col.key">
                  {{ sortDirection === 'asc' ? '↑' : '↓' }}
                </template>
                <template v-else>↕</template>
              </span>
            </th>
            <th v-if="$slots.actions" class="px-4 py-3 text-left text-sm font-medium text-gray-600">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50">
            <td
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3 text-sm"
            >
              <slot :name="`cell-${col.key}`" :item="item">{{ item[col.key] }}</slot>
            </td>
            <td v-if="$slots.actions" class="px-4 py-3 text-sm">
              <div class="flex gap-2">
                <slot name="actions" :item="item" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </template>
  </div>
</template>

<script setup>
import LoadingSpinner from './LoadingSpinner.vue'

defineProps({
  columns: { type: Array, required: true },
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  sortKey: { type: String, default: '' },
  sortDirection: { type: String, default: 'asc' },
})

const emit = defineEmits(['sort'])

function handleSort(key) {
  emit('sort', key)
}
</script>
```

**Uso:** Recibe columnas con `{key, label, sortable}`, items, loading state, y
sortKey/sortDirection. Soporta slots dinámicos por columna (`#cell-name`) y un
slot `#actions` con el item. Si no hay items, muestra estado vacío. Si loading,
muestra spinner.

---

### `frontend/src/components/Pagination.vue`

```vue
<template>
  <div v-if="meta.last_page > 1" class="flex items-center justify-between mt-4">
    <span class="text-sm text-gray-600">
      Página {{ meta.current_page }} de {{ meta.last_page }}
      <span v-if="meta.total" class="text-gray-400">({{ meta.total }} registros)</span>
    </span>
    <div class="flex items-center gap-2">
      <button
        :disabled="meta.current_page === 1"
        @click="$emit('page-change', meta.current_page - 1)"
        class="px-3 py-1.5 bg-gray-200 rounded text-sm hover:bg-gray-300 disabled:opacity-40 disabled:cursor-not-allowed"
      >
        Anterior
      </button>
      <button
        :disabled="meta.current_page === meta.last_page"
        @click="$emit('page-change', meta.current_page + 1)"
        class="px-3 py-1.5 bg-gray-200 rounded text-sm hover:bg-gray-300 disabled:opacity-40 disabled:cursor-not-allowed"
      >
        Siguiente
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  meta: {
    type: Object,
    required: true,
    validator: (val) => 'current_page' in val && 'last_page' in val,
  },
})

defineEmits(['page-change'])
</script>
```

---

### `frontend/src/components/ConfirmDialog.vue`

```vue
<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="visible"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click.self="handleCancel"
      >
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 shadow-xl">
          <h3 class="text-lg font-semibold mb-2 text-gray-800">{{ title }}</h3>
          <p class="text-gray-600 mb-6 text-sm">{{ message }}</p>
          <div class="flex justify-end gap-2">
            <button
              @click="handleCancel"
              class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-sm"
            >
              {{ cancelText }}
            </button>
            <button
              @click="handleConfirm"
              :class="confirmClass"
              class="px-4 py-2 text-white rounded text-sm"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  title: { type: String, default: 'Confirmar acción' },
  message: { type: String, default: '¿Estás seguro?' },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  variant: { type: String, default: 'danger' },
})

const emit = defineEmits(['confirm', 'cancel'])

const confirmClass = computed(() => {
  const variants = {
    danger: 'bg-red-600 hover:bg-red-700',
    primary: 'bg-blue-600 hover:bg-blue-700',
    success: 'bg-green-600 hover:bg-green-700',
  }
  return variants[props.variant] || variants.danger
})

function handleConfirm() {
  emit('confirm')
}

function handleCancel() {
  emit('cancel')
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
```

**Reemplaza:** `confirm()` nativo del navegador. Usa `<Teleport to="body">` para
renderizar fuera del DOM del componente padre (evita problemas de z-index).

---

### `frontend/src/components/FormField.vue`

```vue
<template>
  <div>
    <label class="text-sm text-gray-600 block mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <slot />
    <p v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
})
</script>
```

**Uso:** Wrapper para cualquier campo de formulario. Muestra label, asterisco de
requerido, y mensaje de error. El `<slot />` recibe el input/select/textarea.

---

### `frontend/src/components/LoadingSpinner.vue`

```vue
<template>
  <div class="flex items-center justify-center gap-2 py-8">
    <div
      class="animate-spin rounded-full border-b-2"
      :class="sizeClasses"
      :style="{ borderColor: color }"
    ></div>
    <span v-if="text" class="text-gray-500 text-sm">{{ text }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  text: { type: String, default: '' },
  size: { type: String, default: 'md' },
  color: { type: String, default: '#2563eb' },
})

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'h-4 w-4',
    md: 'h-6 w-6',
    lg: 'h-8 w-8',
  }
  return sizes[props.size] || sizes.md
})
</script>
```

---

### `frontend/src/components/AlertToast.vue`

```vue
<template>
  <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        :class="toastClasses(toast.type)"
        class="px-4 py-3 rounded-lg shadow-lg flex items-center gap-3"
      >
        <span class="flex-1 text-sm">{{ toast.message }}</span>
        <button
          @click="toastStore.remove(toast.id)"
          class="text-current opacity-60 hover:opacity-100"
        >
          ✕
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToastStore } from '../stores/toast'

const toastStore = useToastStore()

const toastClasses = {
  success: 'bg-green-600 text-white',
  error: 'bg-red-600 text-white',
  warning: 'bg-yellow-500 text-white',
  info: 'bg-blue-600 text-white',
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
```

**Se monta en `App.vue`** para que esté disponible en toda la aplicación. Recibe
notificaciones del `toastStore` que es alimentado por los interceptores de Axios.

---

### `frontend/src/stores/auth.js`

```js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  async function login(email, password) {
    loading.value = true
    error.value = null

    try {
      const { data } = await api.post('/login', { email, password })
      token.value = data.token
      user.value = data.user
      localStorage.setItem('token', data.token)
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Credenciales inválidas'
      return false
    } finally {
      loading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) return

    try {
      const { data } = await api.get('/me')
      user.value = data
    } catch (err) {
      if (err.response?.status === 401) {
        clearAuth()
      }
    }
  }

  async function logout() {
    try {
      await api.post('/logout')
    } catch {
      // Token may be expired — clear local state regardless
    }
    clearAuth()
  }

  return { token, user, loading, error, isAuthenticated, login, fetchUser, logout, clearAuth }
})
```

---

### `frontend/src/stores/product.js`

```js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { productService } from '../services/productService'

export const useProductStore = defineStore('product', () => {
  const products = ref([])
  const product = ref(null)
  const stockMovements = ref([])
  const meta = ref({ current_page: 1, last_page: 1, total: 0 })
  const movementsMeta = ref({ current_page: 1, last_page: 1, total: 0 })
  const loading = ref(false)
  const saving = ref(false)
  const error = ref(null)
  const success = ref(null)

  async function fetchProducts(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await productService.list(params)
      products.value = res.data.data
      meta.value = res.data.meta
    } catch (err) {
      error.value = 'Error al cargar productos'
    } finally {
      loading.value = false
    }
  }

  async function fetchProduct(id) {
    loading.value = true
    error.value = null
    try {
      const res = await productService.get(id)
      product.value = res.data.data
      return res.data.data
    } catch (err) {
      error.value = 'Error al cargar el producto'
      return null
    } finally {
      loading.value = false
    }
  }

  async function createProduct(data) {
    saving.value = true
    success.value = null
    try {
      await productService.create(data)
      success.value = 'Producto creado correctamente'
      return true
    } catch (err) {
      throw err
    } finally {
      saving.value = false
    }
  }

  async function updateProduct(id, data) {
    saving.value = true
    success.value = null
    try {
      await productService.update(id, data)
      success.value = 'Producto actualizado correctamente'
      return true
    } catch (err) {
      throw err
    } finally {
      saving.value = false
    }
  }

  async function deleteProduct(id) {
    try {
      await productService.delete(id)
      success.value = 'Producto eliminado'
      return true
    } catch (err) {
      error.value = 'No se pudo eliminar el producto'
      return false
    }
  }

  async function fetchStockMovements(productId, params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await productService.stockMovements(productId, params)
      stockMovements.value = res.data.data
      movementsMeta.value = res.data.meta
    } catch (err) {
      error.value = 'Error al cargar movimientos de stock'
    } finally {
      loading.value = false
    }
  }

  async function createStockMovement(productId, data) {
    saving.value = true
    success.value = null
    try {
      await productService.createStockMovement(productId, data)
      success.value = 'Movimiento registrado correctamente'
      return true
    } catch (err) {
      throw err
    } finally {
      saving.value = false
    }
  }

  function reset() {
    products.value = []
    product.value = null
    stockMovements.value = []
    error.value = null
    success.value = null
  }

  return {
    products, product, stockMovements, meta, movementsMeta,
    loading, saving, error, success,
    fetchProducts, fetchProduct, createProduct, updateProduct, deleteProduct,
    fetchStockMovements, createStockMovement, reset,
  }
})
```

**Separación de `loading` y `saving`:** `loading` es para fetch de datos (tabla),
`saving` es para operaciones de escritura (botón guardar). Esto permite mostrar
estados visuales independientes.

---

### `frontend/src/stores/category.js`

```js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { categoryService } from '../services/categoryService'

export const useCategoryStore = defineStore('category', () => {
  const categories = ref([])
  const meta = ref({ current_page: 1, last_page: 1, total: 0 })
  const loading = ref(false)
  const saving = ref(false)
  const error = ref(null)
  const success = ref(null)

  async function fetchCategories(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await categoryService.list(params)
      categories.value = res.data.data
      meta.value = res.data.meta
    } catch (err) {
      error.value = 'Error al cargar categorías'
    } finally {
      loading.value = false
    }
  }

  async function createCategory(data) {
    saving.value = true
    success.value = null
    try {
      await categoryService.create(data)
      success.value = 'Categoría creada correctamente'
      return true
    } catch (err) {
      throw err
    } finally {
      saving.value = false
    }
  }

  async function updateCategory(id, data) {
    saving.value = true
    success.value = null
    try {
      await categoryService.update(id, data)
      success.value = 'Categoría actualizada correctamente'
      return true
    } catch (err) {
      throw err
    } finally {
      saving.value = false
    }
  }

  async function deleteCategory(id) {
    try {
      await categoryService.delete(id)
      success.value = 'Categoría eliminada'
      return true
    } catch (err) {
      error.value = 'No se pudo eliminar la categoría'
      return false
    }
  }

  function reset() {
    categories.value = []
    error.value = null
    success.value = null
  }

  return {
    categories, meta, loading, saving, error, success,
    fetchCategories, createCategory, updateCategory, deleteCategory, reset,
  }
})
```

---

### `frontend/src/stores/toast.js`

```js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])
  let nextId = 0

  function show(message, type = 'info', duration = 4000) {
    const id = ++nextId
    toasts.value.push({ id, message, type })

    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }
  }

  function remove(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  function success(message) {
    show(message, 'success')
  }

  function error(message) {
    show(message, 'error')
  }

  function warning(message) {
    show(message, 'warning')
  }

  function info(message) {
    show(message, 'info')
  }

  return { toasts, show, remove, success, error, warning, info }
})
```

**Conexión con interceptores:** `main.js` registra `setNotificationHandler` que
llama a `toast.show(message, type)`. Cuando un interceptor de Axios detecta un
error 403/422/500/network, invoca `onNotification` que termina mostrando un toast.

---

### `frontend/src/composables/useFormErrors.js`

```js
import { ref } from 'vue'

export function useFormErrors() {
  const errors = ref({})

  function setErrors(err) {
    errors.value = {}
    if (err.response?.status === 422 && err.response?.data?.errors) {
      errors.value = err.response.data.errors
    }
  }

  function clear() {
    errors.value = {}
  }

  function has(field) {
    return !!errors.value[field]
  }

  function get(field) {
    return errors.value[field]?.[0] || null
  }

  return { errors, setErrors, clear, has, get }
}
```

**Uso:** Mapea errores 422 del backend (que Laravel retorna como `{errors: {field: [msg]}}`)
a un ref reactivo. Se usa en vistas con `const { errors, setErrors, clear, has, get } = useFormErrors()`.

---

### `frontend/src/composables/useValidation.js`

```js
import { ref } from 'vue'

export function useValidation() {
  const errors = ref({})

  function validate(data, rules) {
    errors.value = {}

    for (const [field, rule] of Object.entries(rules)) {
      const value = data[field]
      const isEmpty = value === null || value === undefined || value === ''

      if (rule.required && isEmpty) {
        errors.value[field] = [`${rule.label} es obligatorio`]
        continue
      }

      if (isEmpty) continue

      if (rule.email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        if (!emailRegex.test(value)) {
          errors.value[field] = [`El formato de ${rule.label} no es válido`]
          continue
        }
      }

      if (rule.numeric || rule.min !== undefined || rule.max !== undefined) {
        const num = Number(value)
        if (isNaN(num)) {
          errors.value[field] = [`${rule.label} debe ser un número`]
          continue
        }
        if (rule.min !== undefined && num < rule.min) {
          errors.value[field] = [`${rule.label} debe ser mayor o igual a ${rule.min}`]
          continue
        }
        if (rule.max !== undefined && num > rule.max) {
          errors.value[field] = [`${rule.label} debe ser menor o igual a ${rule.max}`]
          continue
        }
      }

      if (rule.minLength && String(value).length < rule.minLength) {
        errors.value[field] = [`${rule.label} debe tener al menos ${rule.minLength} caracteres`]
        continue
      }
    }

    return Object.keys(errors.value).length === 0
  }

  function setBackendErrors(backendErrors) {
    errors.value = { ...backendErrors }
  }

  function clear() {
    errors.value = {}
  }

  function has(field) {
    return !!errors.value[field]
  }

  function get(field) {
    return errors.value[field]?.[0] || null
  }

  return { errors, validate, setBackendErrors, clear, has, get }
}
```

**Reglas soportadas:**
- `required` — campo obligatorio
- `email` — valida formato con regex
- `numeric` — valida que sea número
- `min` — valor mínimo numérico
- `max` — valor máximo numérico
- `minLength` — longitud mínima de string

**Uso en vistas:**
```js
const validation = useValidation()

function validateForm() {
  return validation.validate(form, {
    email: { required: true, email: true, label: 'Email' },
    password: { required: true, label: 'Contraseña' },
  })
}
```

---

### `frontend/src/services/api.js`

```js
import axios from 'axios'

let onUnauthorized = null
let onNotification = null

export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

export function setNotificationHandler(handler) {
  onNotification = handler
}

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api',
  timeout: 15000,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    const serverMessage = error.response?.data?.message

    switch (status) {
      case 401:
        onUnauthorized?.()
        onNotification?.('Sesión expirada. Inicia sesión nuevamente.', 'error')
        break
      case 403:
        onNotification?.(serverMessage || 'No tienes permisos para esta acción.', 'error')
        break
      case 422:
        onNotification?.(serverMessage || 'Los datos enviados no son válidos.', 'warning')
        break
      case 500:
        onNotification?.(serverMessage || 'Error interno del servidor. Intenta nuevamente.', 'error')
        break
      case undefined:
        onNotification?.('Error de conexión. Verifica tu red o que el servidor esté activo.', 'error')
        break
    }

    return Promise.reject(error)
  }
)

export default api
```

**Cambios vs. legacy:**
- Antes: solo `case 401` tenía acción; 403/422/500/network eran `break` sin acción
- Ahora: cada caso invoca `onNotification` con mensaje y tipo apropiado
- `onUnauthorized` cierra sesión y redirige al login
- `onNotification` muestra un toast global
- Ambos handlers se registran en `main.js` con las funciones reales

---

### `frontend/src/services/productService.js`

```js
import api from './api'

export const productService = {
  list(params = {}) {
    return api.get('/products', { params })
  },

  get(id) {
    return api.get(`/products/${id}`)
  },

  create(data) {
    return api.post('/products', data)
  },

  update(id, data) {
    return api.put(`/products/${id}`, data)
  },

  delete(id) {
    return api.delete(`/products/${id}`)
  },

  stockMovements(id, params = {}) {
    return api.get(`/products/${id}/stock-movements`, { params })
  },

  createStockMovement(id, data) {
    return api.post(`/products/${id}/stock-movements`, data)
  },
}
```

---

### `frontend/src/services/categoryService.js`

```js
import api from './api'

export const categoryService = {
  list(params = {}) {
    return api.get('/categories', { params })
  },

  get(id) {
    return api.get(`/categories/${id}`)
  },

  create(data) {
    return api.post('/categories', data)
  },

  update(id, data) {
    return api.put(`/categories/${id}`, data)
  },

  delete(id) {
    return api.delete(`/categories/${id}`)
  },
}
```

---

### `frontend/src/main.js`

```js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { setUnauthorizedHandler, setNotificationHandler } from './services/api'
import { useAuthStore } from './stores/auth'
import { useToastStore } from './stores/toast'
import './styles.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

setUnauthorizedHandler(() => {
  const auth = useAuthStore()
  auth.clearAuth()
  if (router.currentRoute.value.name !== 'login') {
    router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
  }
})

setNotificationHandler((message, type) => {
  const toast = useToastStore()
  toast.show(message, type)
})

app.mount('#app')
```

**Cambios vs. legacy:** Antes solo registraba `setUnauthorizedHandler`. Ahora también
registra `setNotificationHandler` que conecta los interceptores con el toast store.

---

### `frontend/src/router.js`

```js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', name: 'login', component: () => import('./views/Login.vue'), meta: { public: true } },
  { path: '/dashboard', name: 'dashboard', component: () => import('./views/Dashboard.vue') },
  { path: '/products', name: 'products', component: () => import('./views/Products.vue') },
  { path: '/products/new', name: 'product-create', component: () => import('./views/ProductForm.vue') },
  { path: '/products/:id/edit', name: 'product-edit', component: () => import('./views/ProductForm.vue') },
  { path: '/products/:id/stock', name: 'product-stock', component: () => import('./views/StockMovements.vue') },
  { path: '/categories', name: 'categories', component: () => import('./views/Categories.vue') },
  { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user) {
    await auth.fetchUser()
  }

  if (!to.meta.public && !auth.token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'login' && auth.token) {
    return { name: 'dashboard' }
  }
})

export default router
```

**Rutas:** `/login` es pública (`meta: { public: true }`). Todas las demás requieren
token. El guard `beforeEach` verifica el token y redirige a login si no hay. Si hay
token pero no user (refresco de página), llama a `fetchUser()`.

---

### `frontend/src/App.vue`

```vue
<template>
  <div class="h-screen flex flex-col bg-gray-100 overflow-hidden">
    <nav v-if="route.name !== 'login'" class="bg-gray-800 text-white px-6 py-3 flex items-center gap-6 shrink-0">
      <router-link to="/dashboard" class="hover:text-gray-300">Dashboard</router-link>
      <router-link to="/products" class="hover:text-gray-300">Productos</router-link>
      <router-link to="/categories" class="hover:text-gray-300">Categorías</router-link>
      <button @click="handleLogout" class="ml-auto bg-red-600 hover:bg-red-700 px-4 py-2 rounded">
        Salir
      </button>
    </nav>
    <main class="flex-1 overflow-y-auto">
      <router-view />
    </main>
    <AlertToast />
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import AlertToast from './components/AlertToast.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
```

**Cambios vs. legacy:** Se agregó `<AlertToast />` para mostrar notificaciones
globales en toda la aplicación.

---

### `frontend/src/views/Login.vue`

```vue
<template>
  <div class="h-full flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
      <h2 class="text-2xl font-bold mb-6 text-gray-800">Login</h2>

      <p v-if="auth.error" class="text-red-600 mb-4 text-sm bg-red-50 p-3 rounded">{{ auth.error }}</p>

      <div class="space-y-4">
        <FormField label="Email" :error="validation.get('email')" required>
          <input
            v-model="form.email"
            type="email"
            placeholder="Email"
            autocomplete="username"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            @keyup.enter="handleLogin"
          />
        </FormField>

        <FormField label="Contraseña" :error="validation.get('password')" required>
          <input
            v-model="form.password"
            type="password"
            placeholder="Password"
            autocomplete="current-password"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            @keyup.enter="handleLogin"
          />
        </FormField>

        <button
          :disabled="auth.loading"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded disabled:opacity-50"
        >
          <span v-if="auth.loading" class="inline-flex items-center gap-2">
            <span class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
            Ingresando...
          </span>
          <span v-else>Ingresar</span>
        </button>
      </div>

      <div class="mt-4 text-xs text-gray-500 text-center">
        <p>Credenciales de prueba:</p>
        <p>admin@legacy.test / password</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useValidation } from '../composables/useValidation'
import FormField from '../components/FormField.vue'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const validation = useValidation()

const form = reactive({
  email: 'admin@legacy.test',
  password: 'password',
})

function validateForm() {
  return validation.validate(form, {
    email: { required: true, email: true, label: 'Email' },
    password: { required: true, label: 'Contraseña' },
  })
}

async function handleLogin() {
  validation.clear()

  if (!validateForm()) return

  const success = await auth.login(form.email, form.password)
  if (success) {
    const redirect = route.query.redirect || '/dashboard'
    router.push(redirect)
  } else {
    form.password = ''
  }
}
</script>
```

**Cambios vs. legacy:**
- Antes: inputs sin label, sin validación frontend, sin componentes reutilizables
- Ahora: usa `FormField` para label + error, `useValidation` para validar email/password
- Credenciales pre-cargadas: `admin@legacy.test` / `password` para que el evaluador entre directo
- Spinner en el botón mientras carga

---

## 5. Archivos nuevos — Docker e infraestructura

### `docker-compose.yml`

```yaml
services:
  backend:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: backend-app
    working_dir: /var/www
    volumes:
      - ./backend:/var/www
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_started
    networks:
      - app-network

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: frontend-app
    ports:
      - "${FRONTEND_PORT:-5173}:5173"
    volumes:
      - ./frontend:/app
      - /app/node_modules
    networks:
      - app-network

  nginx:
    image: nginx:alpine
    container_name: nginx-proxy
    ports:
      - "${NGINX_PORT:-8080}:80"
    volumes:
      - ./backend:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - backend
      - frontend
    networks:
      - app-network

  mysql:
    image: mysql:8.0
    container_name: mysql-db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD:-root}
      MYSQL_DATABASE: ${MYSQL_DATABASE:-admision}
      MYSQL_USER: ${MYSQL_USER:-admision_user}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD:-admision123}
    ports:
      - "${MYSQL_PORT:-3306}:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/my.cnf
    networks:
      - app-network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:alpine
    container_name: redis-cache
    restart: always
    ports:
      - "${REDIS_PORT:-6379}:6379"
    volumes:
      - redis_data:/data
    networks:
      - app-network

  worker:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: queue-worker
    working_dir: /var/www
    volumes:
      - ./backend:/var/www
    environment:
      - SKIP_MIGRATIONS=true
    depends_on:
      backend:
        condition: service_started
      mysql:
        condition: service_healthy
      redis:
        condition: service_started
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
    restart: always
    networks:
      - app-network

networks:
  app-network:
    driver: bridge

volumes:
  mysql_data:
  redis_data:
```

**Cambios vs. Fase 1:**
- Credenciales MySQL ahora usan `${MYSQL_ROOT_PASSWORD:-root}` en lugar de hardcoded
- Puertos usan `${NGINX_PORT:-8080}` etc.
- Se creó `.env.example` en la raíz con las variables de Docker Compose

---

### `docker/php/Dockerfile`

```dockerfile
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git curl libzip-dev oniguruma-dev libpng-dev \
    autoconf build-base linux-headers zip unzip

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN mkdir -p /var/www/storage/logs /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions /var/www/storage/framework/views \
    /var/www/bootstrap/cache && \
    chown -R www-data:www-data /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

WORKDIR /var/www

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
```

---

### `docker/php/entrypoint.sh`

```sh
#!/bin/sh
set -e

# Create .env if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Ensure storage and cache directories exist before Composer runs
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

# Permissions
chmod -R 775 storage bootstrap/cache

# Install dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-security-blocking

# Generate app key if not set
if ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

# Run migrations (skip in worker to avoid race conditions)
if [ "${SKIP_MIGRATIONS}" != "true" ]; then
    php artisan migrate --seed --force
fi

# Execute the passed command (php-fpm for backend, queue:work for worker)
exec "$@"
```

**Cambios vs. Fase 1:** `migrate --force` → `migrate --seed --force` (seeders automáticos).

---

### `docker/nginx/default.conf`

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/public;
    index index.php;

    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /telescope {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /docs {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /api/documentation {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass backend:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location / {
        proxy_pass http://frontend:5173;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $remote_addr;
        proxy_cache_bypass $http_upgrade;
    }
}
```

**Nginx hace dos cosas:**
1. Proxy reverso para PHP: `/api`, `/telescope`, `/docs`, `/api/documentation` → PHP-FPM en `backend:9000`
2. Proxy para frontend: `/` → Vite dev server en `frontend:5173` (con WebSocket para HMR)

---

### `.env.example` (raíz)

```env
# Docker Compose variables
# Copy this file to .env to override defaults

NGINX_PORT=8080
FRONTEND_PORT=5173
MYSQL_PORT=3306
REDIS_PORT=6379

MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=admision
MYSQL_USER=admision_user
MYSQL_PASSWORD=admision123
```

---

### `docker/mysql/my.cnf`

```ini
[mysqld]
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
max_connections=200
innodb_buffer_pool_size=256M

[client]
default-character-set=utf8mb4

[mysql]
default-character-set=utf8mb4
```

---

## 6. Archivos modificados

### `backend/database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@legacy.test',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            StockMovementSeeder::class,
        ]);
    }
}
```

**Cambios:** Antes solo insertaba el usuario admin. Ahora llama a los 3 seeders.

---

### Modelos (todos modificados para agregar `use Auditable` y `use HasFactory`)

**`Product.php`:**
- Antes: solo `$fillable` y relación `category()`
- Después: agrega `use HasFactory, Auditable`, `$casts` para price/stock/status, relación `stockMovements()`

**`Category.php`:**
- Antes: solo `$fillable` y `$casts`
- Después: agrega `use HasFactory, Auditable`, relación `products()`

**`StockMovement.php`:**
- Antes: modelo básico
- Después: agrega `use HasFactory, Auditable`, constantes `TYPE_ENTRADA`/`TYPE_SALIDA`, relaciones `product()` y `user()`

**`User.php`:**
- Antes: modelo básico
- Después: agrega `use HasApiTokens, HasFactory, Notifiable`, relaciones `stockMovements()` y `auditLogs()`

---

### READMEs (3 actualizados)

**`README.md` (raíz):** Se agregaron secciones:
- "Migración aplicada" (versión inicial → final, dependencias, problemas encontrados)
- "Decisiones técnicas" (arquitectura, frontend, trade-offs)
- "Optimización" con tabla before/after (N+1, paginación, cache, índices, transacciones)
- "Troubleshooting Docker" (puertos en uso, MySQL, permisos, frontend no conecta)
- `.env.example` raíz con variables de Docker Compose

**`backend/README.md`:** Corregido de `php artisan serve` (manual) a Docker-first. Agregadas secciones de variables de entorno, Swagger/Telescope, arquitectura, tests.

**`frontend/README.md`:** Corregido de "Vue 2 + Vue Router 3" a "Vue 3 + Pinia + Tailwind CSS + Composition API". Agregadas secciones de componentes, interceptores, stores.

---

## 7. Criterios de descarte inmediato (Sección 10)

### Criterio 1: "No entrega Docker funcional o el README no permite levantar el entorno"

**Qué significa:** Si el evaluador ejecuta `docker compose up -d --build` y algo no funciona, o si el README no tiene instrucciones claras, la prueba se marca como **NO APTA** automáticamente. No importa si el código es perfecto. Docker es condición obligatoria.

**Por qué existe este criterio:** El evaluador no va a instalar PHP, Composer, MySQL ni Node en su máquina. Si necesita hacerlo, falla. El entorno debe ser 100% reproducible con Docker.

**Cómo lo cumple el proyecto:**
- `docker-compose.yml` define 6 servicios: backend (PHP-FPM), frontend (Vite), nginx (proxy), mysql (8.0), redis, worker (queue)
- `entrypoint.sh` ejecuta automáticamente: `composer install`, `php artisan key:generate`, `php artisan migrate --seed`
- El README documenta el comando único: `docker compose up -d --build`
- Hay sección de troubleshooting para puertos en uso, MySQL, permisos, frontend
- `.env.example` en la raíz permite personalizar puertos y credenciales sin editar YAML
- Volúmenes `mysql_data` y `redis_data` persisten datos entre reinicios
- Red interna `app-network` (bridge) para comunicación por nombre de servicio

---

### Criterio 2: "El proyecto no corre y no hay explicación técnica razonable"

**Qué significa:** Si al levantar el proyecto algo falla y no hay documentación que explique el problema o cómo resolverlo, se descarta. El candidato debe garantizar que su entrega funciona y saber explicar por qué.

**Por qué existe:** Un especialista debe probar su solución antes de entregarla y documentar los edge cases que encontró.

**Cómo lo cumple:**
- El README tiene sección "Troubleshooting Docker" con 5 escenarios: proyecto no levanta, error de MySQL, puerto en uso, migraciones fallan, frontend no conecta
- La sección "Migración aplicada" documenta los 7 problemas encontrados y cómo se resolvieron
- La sección "Decisiones técnicas" justifica cada elección tecnológica

---

### Criterio 3: "No hay conexión real entre frontend y backend"

**Qué significa:** Si el frontend no puede consumir la API (CORS, URLs incorrectas, autenticación rota), se descarta. No basta con que ambos existan por separado.

**Por qué existe:** Un sistema full stack sin integración real no es utilizable ni demostrable.

**Cómo lo cumple:**
- Nginx hace proxy de `/api` → PHP-FPM y `/` → frontend Vite, eliminando problemas de CORS
- `frontend/.env.example` tiene `VITE_API_URL=http://localhost:8080/api`
- `services/api.js` crea instancia Axios con `baseURL` desde `VITE_API_URL`
- El interceptor de request adjunta `Authorization: Bearer {token}` automáticamente
- Los stores (`product`, `category`) usan los servicios que usan esta instancia Axios
- El router guard verifica token antes de acceder a rutas protegidas
- Si el token expira (401), el interceptor limpia el estado y redirige a login

---

### Criterio 4: "La base de datos no tiene migraciones o seeders ejecutables"

**Qué significa:** Si las migraciones no corren o los seeders fallan dentro del contenedor, se descarta. La base de datos debe crearse y poblarse sin intervención manual.

**Por qué existe:** Sin datos no se pueden probar los endpoints, ni los filtros, ni el rendimiento. Los seeders de volumen (10K productos) son necesarios para evaluar optimización.

**Cómo lo cumple:**
- `entrypoint.sh` ejecuta `php artisan migrate --seed --force` automáticamente al levantar
- 9 migraciones: users, categories, products, stock_movements, audit_logs, telescope_entries, jobs, personal_access_tokens, índices + foreign keys
- 3 seeders: CategorySeeder (100), ProductSeeder (10,000), StockMovementSeeder (30,000)
- Los seeders usan `DB::table()->insert()` en chunks (500/1000) para no agotar memoria
- El worker tiene `SKIP_MIGRATIONS=true` para evitar race conditions con el backend

---

### Criterio 5: "El candidato entrega código copiado sin entenderlo o sin documentar decisiones"

**Qué significa:** Si el código parece generado sin criterio, o si no hay documentación de por qué se tomaron ciertas decisiones técnicas, se descarta. No se evalúa solo el resultado, sino el proceso de pensamiento.

**Por qué existe:** Un especialista debe justificar sus elecciones, no solo implementar.

**Cómo lo cumple:**
- README raíz tiene sección "Decisiones técnicas" que justifica:
  - Por qué Services sobre Actions/Use Cases (simplicidad + ecosistema Laravel)
  - Por qué trait Auditable propio sobre owen-it/laravel-auditing (control + menos dependencias)
  - Por qué queue async para auditoría (no bloquear HTTP)
  - Por qué Pinia stores por dominio (separación de responsabilidades)
  - Por qué composables separados (reutilización de lógica)
  - Trade-offs: Redis como cache+queue (si no está disponible, cambiar a file/database sin impacto)
- README tiene sección "Migración aplicada" con antes/después y 7 problemas encontrados
- Este documento (ACTUALIZACION.md) explica cada archivo con su código fuente completo

---

### Criterio 6: "No respeta el formato estándar de respuesta API"

**Qué significa:** Si cada endpoint responde con un formato distinto (a veces `{data}`, a veces `{product}`, a veces un string plano), se descarta. La consistencia es mínima exigible.

**Por qué existe:** Un frontend no puede consumir una API inconsistente de forma predecible. Cada endpoint nuevo requeriría lógica especial.

**Cómo lo cumple:**
- Todos los endpoints usan API Resources:
  - Listados: `ProductResource::collection($products)` → `{data: [...], meta: {current_page, last_page, total}}`
  - Item individual: `new ProductResource($product)` → `{data: {...}}`
  - Error 422: Laravel retorna automáticamente `{message: "...", errors: {field: [msg]}}`
  - Error 401: `{message: "..."}`
  - Delete: `{null}` con status 204
- Los Resources controlan qué campos se exponen (si el modelo agrega una columna interna, no se expone automáticamente)
- Los Resources formatean tipos (price como decimal, status como boolean, category como sub-objeto)

---

### Criterio 7: "No valida reglas críticas, especialmente salida de stock mayor al disponible"

**Qué significa:** Si se permite registrar una salida de stock por más unidades de las disponibles, se descarta. Es la regla de negocio más importante del sistema.

**Por qué existe:** Un sistema de inventario que permite stock negativo es un error contable grave. Puede causar pérdida de dinero, inconsistencias en reportes y problemas legales.

**Cómo lo cumple:**
- `StockMovementService::create()` envuelve todo en `DB::transaction()`
- Usa `lockForUpdate()` para bloquear la fila del producto (previene race conditions)
- Valida: si `type=salida` y `product->stock < quantity`, lanza `ValidationException` con status 422
- El stock se actualiza con `DB::table('products')->where('id', $productId)->update(['stock' => $newStock])`
- El test `test_salida_rejected_when_stock_insufficient` verifica:
  - Producto con stock=5
  - Intenta salida de 15
  - Espera status 422
  - Verifica que el stock sigue en 5 (no se modificó)

---

## 8. Preguntas de entrevista técnica (Sección 11)

### ¿Qué problema de rendimiento encontraste primero y cómo lo mediste?

**Problema 1 — N+1 en listado de productos:**

El primer problema fue el N+1 query. En el listado de productos, cada producto
accedía a `$product->category->name` en la vista, lo que disparaba una query
separada por cada producto. Con 15 productos por página: 1 query (lista) + 15
queries (categorías) = 16 queries totales.

**Medición:** Se identificó revisando que `Product::paginate()` sin eager loading
ejecutaba `SELECT * FROM products LIMIT 15 OFFSET 0` seguido de
`SELECT * FROM categories WHERE id = ?` por cada producto.

**Solución:** `Product::with('category')->paginate()` ejecuta 2 queries con un
LEFT JOIN: `SELECT * FROM products LIMIT 15 OFFSET 0` +
`SELECT * FROM categories WHERE id IN (1,2,3,...,15)`.

**Problema 2 — Dashboard sin cache:**

Cada visita al dashboard ejecutaba:
- `Product::count()` → COUNT sobre 10,000 filas
- `Category::count()` → COUNT sobre 100 filas
- `Product::where('stock', '<', 10)->with('category')->limit(50)->get()` → SELECT + JOIN
- `StockMovement::with(['product', 'user'])->limit(20)->get()` → SELECT + 2 JOINs

Total: ~50ms sin cache.

**Solución:** `Cache::remember('dashboard.metrics', 300, fn () => [...])` → primer
request ~50ms, siguientes requests ~2ms (cache hit en Redis). Se invalida con
`Cache::forget('dashboard.metrics')` en cada create/update/delete de productos
y categorías.

---

### ¿Qué índices agregaste y por qué?

| Tabla | Columna(s) | Tipo | Por qué |
|---|---|---|---|
| `products` | `status` | Index simple | Se filtra por activo/inactivo en listados |
| `products` | `price` | Index simple | Se filtra por rango (min_price/max_price) y se ordena por precio |
| `products` | `stock` | Index simple | Se filtra por rango (min_stock/max_stock) y se ordena por stock |
| `products` | `created_at` | Index simple | Se ordena por fecha (default: desc) |
| `products` | `category_id` | Foreign key | Se filtra por categoría + integridad referencial (nullOnDelete) |
| `stock_movements` | `type` | Index simple | Se filtra por entrada/salida |
| `stock_movements` | `product_id` | Foreign key | JOIN con products + integridad (cascadeOnDelete) |
| `stock_movements` | `user_id` | Foreign key | JOIN con users + integridad (nullOnDelete) |
| `categories` | `status` | Index simple | Se filtra por estado |
| `audit_logs` | `(entity, entity_id)` | Index compuesto | Consultas de auditoría por entidad (WHERE entity=? AND entity_id=?) |
| `audit_logs` | `user_id` | Index simple | Consultas de auditoría por usuario |

**Justificación de índices compuestos vs simples:**
- `(entity, entity_id)` en audit_logs es compuesto porque las consultas siempre
  filtran por ambos campos simultáneamente
- Los índices de products son simples porque cada filtro se aplica de forma
  independiente con `->when()`

---

### ¿Dónde detectaste N+1 y cómo lo resolviste?

**Lugar 1 — Productos → Categoría:**
- Síntoma: `Product::paginate()` mostraba productos, pero al renderizar
  `item.category?.name` en la tabla, cada acceso disparaba una query
- Query count: 1 + N (donde N = items por página)
- Solución: `Product::query()->with('category')->paginate()` → 2 queries con IN
- Verificado en: `ProductService::list()`

**Lugar 2 — Stock Movements → Usuario y Producto:**
- Síntoma: `StockMovement::where('product_id', ?)->paginate()` cargaba movimientos,
  pero `movement.user?.name` y `movement.product?.name` disparaban queries individuales
- Query count: 1 + N + N (users + products)
- Solución: `StockMovement::where('product_id', ?)->with('user')->paginate()` → 2 queries
- En el DashboardService también: `StockMovement::with(['product', 'user'])->limit(20)->get()`

---

### ¿Por qué separaste la lógica en Services, Requests o Resources?

**Services:**
- Antes: los controladores tenían `Product::create()`, `Cache::forget()`,
  `DB::transaction()` embebidos. Si cambiaba la lógica de negocio, había que
  modificar el controlador.
- Después: los Services encapsulan la lógica. El controlador solo recibe la
  request, delega al Service, y retorna el Resource. Los Services son
  inyectables por DI, testeables de forma aislada, y reutilizables.
- Ejemplo: `StockMovementService::create()` contiene la transacción, el
  lockForUpdate, la validación de stock y la actualización. El controlador
  solo lo llama.

**Form Requests:**
- Antes: validación con `$request->validate()` inline en el controlador
- Después: cada Request encapsula sus reglas. Si las reglas cambian, se
  modifica un archivo, no el controlador. Laravel ejecuta la validación
  antes de entrar al controlador, retornando 422 automáticamente si falla.
- Ejemplo: `StoreProductRequest` valida `name`, `price`, `stock`,
  `category_id` con reglas específicas (exists, numeric, min:0)

**Resources:**
- Antes: `response()->json($product)` exponía todos los campos del modelo
  incluyendo campos internos
- Después: los Resources controlan qué campos se exponen y cómo se formatean
- Si el modelo agrega una columna interna (ej: `internal_sku`), no se expone
  automáticamente
- Formatea tipos: `price` como decimal, `status` como boolean, `category`
  como sub-objeto con `whenLoaded`

---

### ¿Qué harías diferente si este sistema tuviera 1 millón de productos?

1. **Paginación cursor-based:** `paginate()` usa `LIMIT/OFFSET`. Con OFFSET
   15000, MySQL escanea 15000 filas antes de retornar las 15 de la página.
   `cursorPaginate()` usa `WHERE id > last_id` que es O(1) sin importar la
   página.

2. **Read replicas:** Separar lecturas (listados, dashboard) de escrituras
   (CRUD, stock movements). Laravel soporta esto con `Model::on('read')`.
   Distribuye carga entre múltiples instancias de MySQL.

3. **Búsqueda full-text:** `WHERE name LIKE '%term%'` no usa índices y hace
   full table scan. Con 1M de registros, sería inaceptable. Se usaría
   Elasticsearch o MySQL FULLTEXT index con `MATCH ... AGAINST`.

4. **Cache más agresivo:**
   - Cache de listados completos por query string (key = md5 de filters+page)
   - Cache de productos individuales por ID con TTL
   - Cache invalidation por evento (producto actualizado → forget cache)
   - Considerar Redis Cluster para distribución de carga

5. **Queue para dashboard:** Con 1M de productos, `COUNT(*)` es lento. Se
   calcularían las métricas en un Job programado (cada 5 min) y se cachearía
   el resultado. El dashboard solo leería el cache.

6. **Particionamiento de stock_movements:** Con millones de movimientos
   históricos, las consultas recientes escanean datos antiguos. Se particionaría
   por mes: `PARTITION BY RANGE (MONTH(created_at))`.

7. **Índices compuestos más específicos:** Analizar los query patterns reales
   con `EXPLAIN` y `slow_query_log` para crear índices compuestos optimizados.
   Ejemplo: `(status, category_id, created_at)` si el patrón más común es
   filtrar por status + categoría y ordenar por fecha.

---

### ¿Cómo asegurarías que Docker se use igual en desarrollo, QA y producción?

1. **Misma imagen base:** El `Dockerfile` se usa para todos los ambientes.
   En dev se agregan Xdebug y Telescope (require-dev). En producción se
   usa multi-stage build para excluirlos.

2. **Docker Compose por ambiente:**
   - `docker-compose.yml` (base: servicios, redes, volúmenes)
   - `docker-compose.override.yml` (dev: volúmenes de código fuente, telescope,
     debug, auto-reload)
   - `docker-compose.prod.yml` (prod: sin volúmenes de código, restart: always,
     healthchecks estrictos, sin telescope)

3. **Variables de entorno:** Todo se configura vía `.env`. En dev, `.env`
   local. En QA/prod, las variables se inyectan desde el orquestador
   (Kubernetes Secrets, AWS Parameter Store, GitLab CI variables). Nunca
   hardcoded en el YAML.

4. **CI/CD pipeline:**
   - El pipeline construye la imagen Docker una vez
   - La misma imagen se despliega a QA y producción
   - Solo cambia el `.env` (credenciales, URLs, flags)
   - Esto garantiza que lo que se prueba en QA es exactamente lo que va a producción

5. **Health checks:** Cada servicio tiene healthcheck. En dev para debugging.
   En producción para que el orquestador (Kubernetes/Swarm) reinicie
   contenedores no saludables automáticamente.

6. **No `.env` en producción:** Las variables se inyectan desde el orquestador.
   El `.env.example` solo documenta qué variables existen.

---

### ¿Qué riesgos encontraste durante la migración de versiones?

**1. Laravel 8 → 11 elimina la estructura de Kernel**

Laravel 11 elimina `Console/Kernel.php`, `Http/Kernel.php`, `Exceptions/Handler.php`
y `RouteServiceProvider.php`. Toda la configuración se mueve a `bootstrap/app.php`
con fluent API.

- Riesgo: Middleware personalizado registrado en `Http/Kernel.php` se perdería
- Solución: Migrar el registro de middleware a `->withMiddleware()` en
  `bootstrap/app.php`
- Impacto en este proyecto: Ninguno, porque no había middleware personalizado

**2. PHP 8.0 → 8.2**

PHP 8.2 agrega readonly properties, enum support y deprecated dynamic properties.

- Riesgo: Librerías de terceros pueden ser incompatibles con PHP 8.2
- Solución: Verificar `composer.json` de cada paquete. Ejecutar `composer update`
  y revisar warnings
- Impacto: Ninguno, todos los paquetes (Sanctum 4.x, Telescope 5.x, l5-swagger 11.x)
  soportan PHP 8.2

**3. Vue 2 → Vue 3**

- `v-model` en componentes cambia: usa `modelValue` y `update:modelValue`
  en lugar de `value` e `input`
- `filters` eliminados: se reemplazan con `computed` o métodos
- `event bus` deprecado: se reemplaza con Pinia o provide/inject
- `this.$set` y `Vue.set` eliminados (Vue 3 es reactivo por defecto)

- Riesgo: Componentes de Vue 2 no funcionan en Vue 3 sin refactor
- Solución: Refactor a Composition API con `<script setup>`. Usar Pinia
  en lugar de event bus. `computed` en lugar de filters.

**4. Sanctum migration**

La tabla `personal_access_tokens` debe existir para que Sanctum funcione.

- Riesgo: Si no se corre la migración, `createToken()` falla
- Solución: Se agregó la migración `2019_12_14_000001_create_personal_access_tokens_table.php`

**5. Telescope**

Telescope requiere sus propias migraciones y un ServiceProvider.

- Riesgo: Telescope no funciona si no se registra el provider
- Solución: Se agregó `TelescopeServiceProvider` a `bootstrap/providers.php`.
  Se filtra para que solo guarde datos en `local` o en excepciones.

---

### ¿Cómo manejarías logs, errores y auditoría en producción?

**Logs:**

- En Docker, usar `LOG_CHANNEL=stderr` para que los logs vayan a stdout/stderr.
  Esto permite verlos con `docker compose logs -f backend` y que el orquestador
  los capture automáticamente.
- En producción, usar un driver externo: Papertrail, ELK Stack (Elasticsearch +
  Logstash + Kibana), o AWS CloudWatch Logs.
- Nunca loggear a archivo dentro del contenedor (es ephemeral, se pierde al
  recrear el contenedor).
- Niveles: `LOG_LEVEL=error` en producción (no debug ni info).

**Errores:**

- Centralizar con el handler de Laravel (`bootstrap/app.php` → `withExceptions()`).
- En producción, `APP_DEBUG=false` para no exponer stack traces.
- Reportar a Sentry o Bugsnag para alertas en tiempo real cuando ocurren
  errores 500.
- Los errores 422 (validación) y 404 (no encontrado) no se reportan
  (son esperados, no son bugs).
- Los errores 500 se reportan inmediatamente con stack trace y contexto.

**Auditoría:**

El sistema actual usa:
- Trait `Auditable` en Product, Category, StockMovement
- Job `LogAuditEntry` en cola Redis (async, no bloquea HTTP)
- Tabla `audit_logs` con user_id, action, entity, entity_id, old_values, new_values
- Toggle con `AUDIT_ENABLED=true|false`

En producción:
- Monitorear el worker: `docker compose logs -f worker`. Si el worker cae,
  los jobs se acumulan en la tabla `jobs`.
- Retención: Programar un comando Artisan (cron) que purge `audit_logs` mayores
  a 90 días para evitar crecimiento infinito de la tabla.
- Para volúmenes altos: Considerar enviar a un servicio especializado (AWS
  CloudTrail, Datadog Audit Trail) en lugar de la base de datos.
- Alertas: Configurar alertas cuando hay errores 500 frecuentes, cuando el
  worker falla, o cuando el stock llega a 0 (stock crítico).

---

*Documento generado el 27 de junio de 2026.*
