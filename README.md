# Sistema de Gestión de Productos — Laravel 11 + Vue.js + Docker

Sistema de gestión de productos con categorías, movimientos de stock y autenticación.

## Requisitos

- Docker
- Docker Compose

## Instalación

```bash
git clone <repo-url>
cd prueba_tecnica
docker compose up -d --build
```

La primera vez, el backend ejecuta automáticamente `composer install` y genera el `APP_KEY`.

### Migración de base de datos

```bash
docker compose exec backend php artisan migrate --seed
```

## URLs de acceso

| Servicio  | URL                              |
|-----------|----------------------------------|
| App       | http://localhost:8080            |
| API       | http://localhost:8080/api        |
| Health    | http://localhost:8080/api/health |
| Frontend  | http://localhost:5173            |
| MySQL     | localhost:3306                   |
| Redis     | localhost:6379                   |

## Variables de entorno

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
```

### Frontend (`frontend/.env.example`)

```env
VITE_API_URL=http://localhost:8080/api
```

## Servicios Docker

| Servicio  | Contenedor     | Imagen           | Puerto  |
|-----------|----------------|------------------|---------|
| Backend   | backend-app    | PHP 8.2 FPM      | 9000    |
| Frontend  | frontend-app   | Node 20 + Vite   | 5173    |
| Nginx     | nginx-proxy    | nginx:alpine     | 8080    |
| MySQL     | mysql-db       | mysql:8.0        | 3306    |
| Redis     | redis-cache    | redis:alpine     | 6379    |

## Estructura del proyecto

```
prueba_tecnica/
├── docker/
│   ├── nginx/
│   │   └── default.conf        # Proxy reverso: /api → PHP-FPM, / → Vite
│   ├── php/
│   │   ├── Dockerfile          # PHP 8.2 FPM + extensiones + Composer
│   │   └── entrypoint.sh       # composer install, key:generate, php-fpm
│   └── mysql/
│       └── my.cnf              # Configuración MySQL (utf8mb4)
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/    # Auth, Product, Category, Dashboard
│   │   │   └── Middleware/     # LegacyTokenAuth
│   │   ├── Models/             # Product, Category, StockMovement, User
│   │   ├── Providers/          # AppServiceProvider
│   │   └── Services/          # (vacío - pendiente)
│   ├── bootstrap/
│   │   ├── app.php             # Configuración Laravel 11
│   │   └── providers.php       # Service providers
│   ├── config/
│   │   ├── app.php
│   │   ├── database.php         # MySQL + Redis
│   │   └── logging.php
│   ├── database/
│   │   ├── migrations/         # users, categories, products, stock_movements
│   │   └── seeders/            # DatabaseSeeder
│   ├── routes/
│   │   └── api.php             # Endpoints API
│   ├── composer.json
│   └── .env.example
├── frontend/
│   ├── src/
│   │   ├── views/              # Login, Dashboard, Products, Categories, StockMovements
│   │   ├── api.js              # Cliente Axios centralizado
│   │   ├── router.js
│   │   └── styles.css
│   ├── Dockerfile
│   ├── vite.config.js
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
docker compose logs -f frontend
docker compose logs -f nginx

# Ejecutar comandos en el backend
docker compose exec backend php artisan route:list
docker compose exec backend php artisan migrate
docker compose exec backend php artisan migrate:rollback
docker compose exec backend php artisan db:seed
docker compose exec backend php artisan tinker
docker compose exec backend composer require <package>

# Ejecutar comandos en el frontend
docker compose exec frontend npm install <package>
docker compose exec frontend npm run build

# Acceder a MySQL
docker compose exec mysql mysql -u admision_user -padmision123 admision

# Acceder a Redis
docker compose exec redis redis-cli
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

## Estado actual

### Completado
- [x] Docker consolidado (backend, frontend, MySQL, Nginx, Redis)
- [x] PHP 8.2 + Laravel 11
- [x] Red interna Docker con volúmenes persistentes
- [x] Nginx como proxy reverso
- [x] Configuración Redis para cache/queue/session

### Pendiente
- [ ] Sanctum para autenticación (reemplazar token manual legacy)
- [ ] Arquitectura: Services, Requests, Resources
- [ ] Validaciones con Form Requests
- [ ] Transacciones DB en movimientos de stock
- [ ] Paginación, filtros y ordenamiento
- [ ] Eager loading (eliminar N+1)
- [ ] Índices en migraciones
- [ ] Cache con `Cache::remember()`
- [ ] Swagger (l5-swagger)
- [ ] Telescope
- [ ] Auditoría (audit_logs)
- [ ] Tests
- [ ] Migrar frontend a Vue 3 + Composition API
- [ ] Pinia (stores)
- [ ] Tailwind CSS
- [ ] Axios centralizado con interceptores
- [ ] Componentes reutilizables
