# Backend — Laravel 11 + PHP 8.2

API REST para gestión de productos, categorías y movimientos de stock.

## Stack

- Laravel 11
- PHP 8.2
- MySQL 8.0
- Redis (cache, queue, session)

## Instalación

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## URL base

```txt
http://localhost:8080/api
```

## Credenciales

```txt
email: admin@legacy.test
password: password
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
