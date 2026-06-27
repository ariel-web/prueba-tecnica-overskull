# Sistema de Gestión de Productos — Laravel 11 + Vue 3 + Docker

Sistema de gestión de productos con categorías, movimientos de stock, auditoría y autenticación.

## Requisitos

- Docker
- Docker Compose

## Instalación

```bash
git clone <repo-url>
cd prueba_tecnica
docker compose up -d --build
```

La primera vez, el backend ejecuta automáticamente `composer install`, genera el `APP_KEY`, corre las migraciones y seeders. El worker de cola arranca automáticamente.

> Opcional: Copiar `.env.example` de la raíz a `.env` para personalizar puertos y credenciales de Docker Compose.

### Migración de base de datos

Las migraciones y seeders se ejecutan automáticamente al levantar el contenedor. Para reejecutar manualmente:

```bash
docker compose exec backend php artisan migrate --seed
# O reiniciar desde cero:
docker compose exec backend php artisan migrate:fresh --seed
```

### Credenciales por defecto

- Email: `admin@legacy.test`
- Password: `password`

## URLs de acceso

| Servicio   | URL                              |
|------------|----------------------------------|
| App        | http://localhost:8080            |
| API        | http://localhost:8080/api        |
| Swagger    | http://localhost:8080/api/documentation |
| Telescope  | http://localhost:8080/telescope  |
| Health     | http://localhost:8080/api/health |
| Frontend   | http://localhost:5173            |
| MySQL      | localhost:3306                   |
| Redis      | localhost:6379                   |

## Variables de entorno

### Docker Compose (`.env.example` raíz)

Variables para personalizar puertos y credenciales de MySQL:

```env
NGINX_PORT=8080
FRONTEND_PORT=5173
MYSQL_PORT=3306
REDIS_PORT=6379

MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=admision
MYSQL_USER=admision_user
MYSQL_PASSWORD=admision123
```

### Backend (`backend/.env.example`)

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

### Frontend (`frontend/.env.example`)

```env
VITE_API_URL=http://localhost:8080/api
```

## Servicios Docker

| Servicio  | Contenedor     | Imagen           | Puerto  |
|-----------|----------------|------------------|---------|
| Backend   | backend-app    | PHP 8.2 FPM      | 9000    |
| Worker    | queue-worker   | PHP 8.2 (queue)  | -       |
| Frontend  | frontend-app   | Node 20 + Vite   | 5173    |
| Nginx     | nginx-proxy    | nginx:alpine     | 8080    |
| MySQL     | mysql-db       | mysql:8.0        | 3306    |
| Redis     | redis-cache    | redis:alpine     | 6379    |

## Estructura del proyecto

```
prueba_tecnica/
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   ├── php/
│   │   ├── Dockerfile
│   │   └── entrypoint.sh
│   └── mysql/
│       └── my.cnf
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/    # Auth, Product, Category, Dashboard
│   │   │   ├── Requests/       # Validaciones (Auth, Product, Category)
│   │   │   ├── Resources/      # API Resources
│   │   │   └── Middleware/
│   │   ├── Models/             # Product, Category, StockMovement, User, AuditLog
│   │   ├── Services/           # ProductService, CategoryService, StockMovementService, AuthService, AuditService
│   │   ├── Jobs/               # LogAuditEntry (queued)
│   │   ├── Traits/             # Auditable trait
│   │   ├── Swagger/            # OpenAPI annotations y schemas
│   │   └── Providers/          # AppServiceProvider, TelescopeServiceProvider
│   ├── config/                 # app, database, telescope, l5-swagger, audit, sanctum
│   ├── database/
│   │   ├── migrations/         # users, categories, products, stock_movements, audit_logs, telescope
│   │   ├── factories/          # User, Category, Product, StockMovement
│   │   └── seeders/            # DatabaseSeeder (100 cat, 10k prod, 30k mov)
│   ├── routes/api.php
│   ├── tests/Feature/          # AuthTest, CategoryTest, ProductTest, StockMovementTest
│   └── .env.example
├── frontend/
│   ├── src/
│   │   ├── views/              # Login, Dashboard, Products, Categories, StockMovements, ProductForm
│   │   ├── components/         # DataTable, Pagination, ConfirmDialog, FormField, LoadingSpinner, AlertToast
│   │   ├── services/           # api.js (interceptores), productService.js, categoryService.js
│   │   ├── stores/             # auth.js, product.js, category.js, toast.js (Pinia)
│   │   ├── composables/        # useFormErrors.js, useValidation.js
│   │   ├── router.js
│   │   ├── main.js
│   │   └── styles.css
│   ├── Dockerfile
│   ├── vite.config.js
│   ├── tailwind.config.js
│   └── .env.example
├── docker-compose.yml
└── README.md
```

## Comandos útiles

```bash
# Levantar servicios
docker compose up -d --build

# Detener servicios
docker compose down

# Ver logs
docker compose logs -f backend
docker compose logs -f worker
docker compose logs -f frontend

# Ejecutar tests
docker compose exec backend php artisan test

# Acceder a MySQL
docker compose exec mysql mysql -u admision_user -padmision123 admision

# Acceder a Redis
docker compose exec redis redis-cli

# Regenerar docs Swagger
docker compose exec backend php artisan l5-swagger:generate
```

## Endpoints API

| Método   | Endpoint                            | Descripción           |
|----------|-------------------------------------|-----------------------|
| POST     | /api/login                          | Iniciar sesión        |
| POST     | /api/logout                         | Cerrar sesión         |
| GET      | /api/me                             | Usuario actual        |
| GET      | /api/health                         | Health check          |
| GET      | /api/dashboard                      | Métricas del dashboard |
| GET      | /api/categories                     | Listar categorías     |
| POST     | /api/categories                     | Crear categoría       |
| GET      | /api/categories/{id}                | Ver categoría         |
| PUT      | /api/categories/{id}                | Actualizar categoría  |
| DELETE   | /api/categories/{id}                | Eliminar categoría    |
| GET      | /api/products                       | Listar productos      |
| POST     | /api/products                       | Crear producto        |
| GET      | /api/products/{id}                  | Ver producto          |
| PUT      | /api/products/{id}                  | Actualizar producto   |
| DELETE   | /api/products/{id}                  | Eliminar producto     |
| GET      | /api/products/{id}/stock-movements  | Movimientos de stock  |
| POST     | /api/products/{id}/stock-movements  | Registrar movimiento  |

## Auditoría

La auditoría registra automáticamente los eventos `create`, `update` y `delete` de los modelos Product, Category y StockMovement mediante el trait `Auditable`.

- Los logs se guardan en la tabla `audit_logs`
- El registro se hace en cola (queue) mediante el job `LogAuditEntry`
- El worker de cola arranca automáticamente con `docker compose up`
- Se puede activar/desactivar con la variable `AUDIT_ENABLED=true|false` en `.env`

## Optimización

### Problemas detectados y soluciones

| Problema | Antes | Después | Mejora |
|---|---|---|---|
| N+1 en listado de productos | Sin eager loading → 1 + N queries | `Product::with('category')` → 2 queries | Reduce de ~16 a 2 queries (15 items/página) |
| Listados sin paginación | `Product::all()` cargaba todos los registros | `Product::paginate(15)` con filtros | Memoria y tiempo de respuesta reducidos |
| Consultas de dashboard sin cache | Cada visita ejecutaba COUNT + JOIN | `Cache::remember(300s)` para métricas | De ~50ms a ~2ms en cache hit |
| Listado de categorías sin cache | Query por cada página visitada | `Cache::remember(600s)` con flush en writes | De ~15ms a ~1ms en cache hit |
| Stock movements sin transacción | Update de stock sin lock → race condition | `DB::transaction` + `lockForUpdate()` | Previene inconsistencia de stock en concurrencia |
| Sin índices en filtros | Full table scan en filtros por status/price | Índices compuestos en products(status, price, stock, created_at) | Scan → index seek |

### Optimizaciones aplicadas

- **Paginación:** Todos los listados usan `paginate()` (15 items por defecto, configurable con `per_page`)
- **Filtros:** Por nombre (`q`), categoría, estado, rango de precio (`min_price`/`max_price`) y stock (`min_stock`/`max_stock`)
- **Ordenamiento:** Por `price`, `stock` y `created_at` con dirección `asc`/`desc`
- **Eager loading:** `Product::with('category')` y `StockMovement::with('user', 'product')` para evitar N+1
- **Índices:** category_id, name, status, price, stock, created_at en products; type en stock_movements; entity, entity_id, user_id en audit_logs
- **Cache:** `Cache::remember()` en dashboard (300s) y categorías (600s) con flush automático en writes
- **Transacciones:** `DB::transaction()` + `lockForUpdate()` en movimientos de stock
- **Queue:** Auditoría en cola asíncrona vía Redis para no bloquear respuestas HTTP
- **Seeder de volumen:** 100 categorías, 10,000 productos y 30,000 movimientos de stock para probar rendimiento

## Tests

```bash
docker compose exec backend php artisan test
```

Cobertura:
- Login (success, invalid, validation)
- Categorías (CRUD, validación, auth)
- Productos (CRUD, filtros, paginación, auth)
- Stock (entrada, salida, stock insuficiente, validación)

## Migración aplicada

### Versión inicial (legacy)

- Laravel 9 con PHP 8.0
- Controladores con lógica de negocio embebida (fat controllers)
- Sin Form Requests ni Resources
- Vue 2 con Options API
- Axios repetido en cada componente
- Sin Pinia, sin Tailwind
- Sin Docker, dependencias manuales
- Sin Swagger, sin Telescope, sin auditoría
- Seeders con ~126 productos y ~15 categorías

### Versión final

- Laravel 11 con PHP 8.2
- Arquitectura por capas: Controllers → Form Requests → Services → Resources
- Vue 3 con Composition API + Vite 5
- Pinia para estado global (auth, product, category, toast)
- Axios centralizado con interceptores globales (401, 403, 422, 500, network)
- Tailwind CSS para UI consistente
- Componentes reutilizables (DataTable, Pagination, ConfirmDialog, FormField, LoadingSpinner, AlertToast)
- Docker con backend, frontend, nginx, MySQL, Redis, worker
- Swagger/OpenAPI con anotaciones en controladores
- Laravel Telescope para debugging en desarrollo
- Auditoría automática con trait Auditable (async vía queue)
- Seeders de volumen: 100 categorías, 10,000 productos, 30,000 movimientos

### Dependencias actualizadas

| Paquete | Antes | Después |
|---|---|---|
| Laravel Framework | 9.x | 11.x |
| PHP | 8.0 | 8.2 |
| Vue | 2.x | 3.4 |
| Vue Router | 3.x | 4.3 |
| Vite | N/A | 5.0 |
| Pinia | N/A | 2.1 |

### Problemas encontrados y solución

1. **Fat controllers**: La lógica de negocio estaba en los controladores. Se extrajo a Services con inyección por DI.
2. **Respuestas inconsistentes**: Se estandarizaron con API Resources.
3. **Sin validaciones**: Se crearon Form Requests para cada endpoint.
4. **N+1 en productos**: Se agregó eager loading `with('category')`.
5. **Race condition en stock**: Se implementó `DB::transaction` + `lockForUpdate()`.
6. **Sin manejo global de errores**: Se centralizó con interceptor Axios + toast store.
7. **Componentes no reutilizables**: Se crearon DataTable, Pagination, ConfirmDialog, etc.

## Decisiones técnicas

### Arquitectura

- **Services layer**: Se eligió Services sobre Actions/Use Cases por simplicidad y consistencia con el ecosistema Laravel. Cada Service encapsula la lógica de un dominio (ProductService, CategoryService, StockMovementService, AuthService, AuditService, DashboardService).
- **Form Requests**: Se separaron las validaciones del controlador para mantenerlos delgados y reutilizar reglas.
- **API Resources**: Se usan para estandarizar respuestas JSON y desacoplar la estructura interna de modelos de la API expuesta.
- **Auditable trait**: Se implementó un trait propio en lugar de un paquete externo (owen-it/laravel-auditing) para tener control total del formato y almacenamiento, y para minimizar dependencias.
- **Queue asíncrona**: La auditoría se guarda en cola (Redis) para no bloquear la respuesta HTTP. El worker se levanta automáticamente con Docker Compose.

### Frontend

- **Pinia stores por dominio**: Se crearon stores separados para auth, product, category y toast. Cada store maneja su propio estado de loading/error/success.
- **Composables**: `useFormErrors` (mapeo de errores 422 del backend) y `useValidation` (validación frontend) mantienen la lógica fuera de los componentes.
- **Componentes reutilizables**: DataTable con slots dinámicos, Pagination, ConfirmDialog con Teleport, FormField con label/error, LoadingSpinner, AlertToast con TransitionGroup.
- **Interceptor global**: Se centralizó el manejo de errores 401/403/422/500/network con notificaciones toast automáticas.

### Trade-offs

- **Cache con Redis**: Se usa Redis para cache y colas. Si Redis no está disponible, se puede cambiar a file/database en `.env` sin impacto en código.
- **Telescope en require-dev**: Se mantiene solo en desarrollo para evitar overhead en producción.
- **Seeder directo vs Factory**: Los seeders de volumen usan `DB::table()->insert()` en chunks en lugar de factories para mejor rendimiento con 10K+ registros.

## Troubleshooting Docker

### El proyecto no levanta

```bash
# Verificar que no hay contenedores anteriores corriendo
docker compose down

# Reconstruir desde cero
docker compose up -d --build

# Ver logs en tiempo real
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f nginx
```

### Error de conexión a MySQL

```bash
# Verificar que MySQL esté healthy
docker compose ps

# Si MySQL no inicia, borrar el volumen y recrear
docker compose down -v
docker compose up -d --build

# Acceder a MySQL para verificar
docker compose exec mysql mysql -u admision_user -padmision123 admision
```

### Puerto 8080 o 5173 ya en uso

Editar `.env` en la raíz del proyecto:

```env
NGINX_PORT=8081
FRONTEND_PORT=5174
```

### Migraciones o seeders fallan

```bash
# Reejecutar migraciones y seeders desde cero
docker compose exec backend php artisan migrate:fresh --seed

# Solo seeders
docker compose exec backend php artisan db:seed

# Regenerar Swagger
docker compose exec backend php artisan l5-swagger:generate
```

### Frontend no conecta con la API

- Verificar que `VITE_API_URL` en `frontend/.env` apunte a `http://localhost:8080/api`
- Si se cambió el puerto de nginx, actualizar `VITE_API_URL` en consecuencia
- Reiniciar el contenedor frontend: `docker compose restart frontend`

### Permisos de storage

```bash
docker compose exec backend chmod -R 775 storage bootstrap/cache
```

## Estado actual

### Completado
- [x] Docker (backend, frontend, MySQL, Nginx, Redis, worker)
- [x] Variables de entorno con `${VAR}` en docker-compose.yml
- [x] Auto-seed en entrypoint (`migrate --seed`)
- [x] PHP 8.2 + Laravel 11
- [x] Sanctum para autenticación
- [x] Arquitectura: Controllers → Requests → Services → Resources
- [x] Validaciones con Form Requests
- [x] API Resources
- [x] Transacciones DB en movimientos de stock
- [x] Paginación, filtros y ordenamiento
- [x] Eager loading (eliminar N+1)
- [x] Índices en migraciones
- [x] Cache con `Cache::remember()`
- [x] Telescope
- [x] Swagger / OpenAPI annotations
- [x] Auditoría automática con trait Auditable (en cola)
- [x] Variable AUDIT_ENABLED para activar/desactivar auditoría
- [x] Queue worker automático con docker compose
- [x] Factories y Seeders (100 cat, 10k prod, 30k mov)
- [x] Tests (Auth, Categories, Products, StockMovements)
- [x] Vue 3 + Composition API
- [x] Pinia (auth, product, category, toast stores)
- [x] Tailwind CSS
- [x] Axios centralizado con interceptores globales (401, 403, 422, 500, Network Error)
- [x] Servicios centralizados (productService, categoryService)
- [x] Componentes reutilizables (DataTable, Pagination, ConfirmDialog, FormField, LoadingSpinner, AlertToast)
- [x] Validaciones frontend con composable useValidation
- [x] Controladores delgados
- [x] Documentación: migración, optimización, decisiones técnicas, troubleshooting
