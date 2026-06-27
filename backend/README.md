# Backend — Laravel 11 + PHP 8.2

API REST para gestión de productos, categorías y movimientos de stock.

## Stack

- Laravel 11
- PHP 8.2
- MySQL 8.0
- Redis (cache, queue, session)
- Laravel Sanctum (autenticación)
- Laravel Telescope (debugging)
- Swagger/OpenAPI (documentación)

## Requisitos

- Docker
- Docker Compose

No es necesario tener PHP, Composer o MySQL instalados localmente.

## Instalación con Docker

```bash
# Desde la raíz del proyecto
docker compose up -d --build

# El entrypoint ejecuta automáticamente:
# - composer install
# - php artisan key:generate
# - php artisan migrate --seed
```

## Variables de entorno

Copiar `backend/.env.example` a `backend/.env` (se hace automáticamente en Docker):

```env
APP_NAME=BackendLaravel11
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=admision
DB_USERNAME=admision_user
DB_PASSWORD=admision123

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PORT=6379

AUDIT_ENABLED=true
TELESCOPE_ENABLED=true
L5_SWAGGER_GENERATE_ALWAYS=true
```

## URL base

```txt
http://localhost:8080/api
```

## Documentación

| Servicio  | URL                                    |
|-----------|----------------------------------------|
| Swagger   | http://localhost:8080/api/documentation|
| Telescope | http://localhost:8080/telescope        |
| Health    | http://localhost:8080/api/health       |

## Credenciales de prueba

```txt
email: admin@legacy.test
password: password
```

## Comandos útiles (en Docker)

```bash
# Migraciones y seeders
docker compose exec backend php artisan migrate --seed

# Reiniciar desde cero
docker compose exec backend php artisan migrate:fresh --seed

# Tests
docker compose exec backend php artisan test

# Regenerar Swagger
docker compose exec backend php artisan l5-swagger:generate

# Limpiar cache
docker compose exec backend php artisan optimize:clear
```

## Endpoints

```txt
POST   /api/login
POST   /api/logout
GET    /api/me
GET    /api/health
GET    /api/dashboard
GET    /api/categories
POST   /api/categories
GET    /api/categories/{id}
PUT    /api/categories/{id}
DELETE /api/categories/{id}
GET    /api/products
POST   /api/products
GET    /api/products/{id}
PUT    /api/products/{id}
DELETE /api/products/{id}
GET    /api/products/{id}/stock-movements
POST   /api/products/{id}/stock-movements
```

## Arquitectura

```
app/
├── Http/
│   ├── Controllers/     # Auth, Product, Category, Dashboard (delgados)
│   ├── Requests/       # Validaciones (Login, Store/Update Product, Store/Update Category, StockMovement)
│   └── Resources/       # API Resources (Product, Category, StockMovement, AuditLog)
├── Models/              # Product, Category, StockMovement, User, AuditLog
├── Services/            # ProductService, CategoryService, StockMovementService, AuthService, AuditService, DashboardService
├── Jobs/                # LogAuditEntry (auditoría en cola)
├── Traits/              # Auditable trait
├── Swagger/             # OpenAPI schemas
└── Providers/           # AppServiceProvider, TelescopeServiceProvider
```

## Tests

```bash
docker compose exec backend php artisan test
```

Cobertura: Login, Categorías (CRUD, validación, auth), Productos (CRUD, filtros, paginación, auth), Stock (entrada, salida, stock insuficiente, validación).
