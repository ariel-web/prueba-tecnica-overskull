# Frontend — Vue 3 + Vite + Pinia + Tailwind CSS

Interfaz de gestión de productos, categorías y movimientos de stock.

## Stack

- Vue 3 (Composition API)
- Vite 5
- Pinia (manejo de estado global)
- Vue Router 4 (rutas protegidas y públicas)
- Axios (servicios centralizados con interceptores)
- Tailwind CSS (UI consistente)

## Requisitos

- Docker
- Docker Compose

No es necesario tener Node.js instalado localmente.

## Instalación con Docker

```bash
# Desde la raíz del proyecto
docker compose up -d --build
```

El frontend se levanta automáticamente en `http://localhost:5173`.

## Variables de entorno

Copiar `frontend/.env.example` a `frontend/.env`:

```env
VITE_API_URL=http://localhost:8080/api
```

## Arquitectura

```
src/
├── views/              # Login, Dashboard, Products, ProductForm, Categories, StockMovements
├── components/         # DataTable, Pagination, ConfirmDialog, FormField, LoadingSpinner, AlertToast
├── services/           # api.js (interceptores), productService.js, categoryService.js
├── stores/             # auth.js, product.js, category.js, toast.js (Pinia)
├── composables/        # useFormErrors.js (errores 422), useValidation.js (validación frontend)
├── router.js           # Rutas públicas y protegidas con guards
├── main.js
└── styles.css          # Tailwind CSS
```

## Componentes reutilizables

| Componente | Uso |
|---|---|
| DataTable | Tabla con slots dinámicos, sorting y estado de carga |
| Pagination | Control de paginación reutilizable |
| ConfirmDialog | Modal de confirmación con Teleport |
| FormField | Wrapper de campo con label y error |
| LoadingSpinner | Indicador de carga |
| AlertToast | Notificaciones globales (success, error, warning, info) |

## Interceptores Axios

Manejo centralizado de errores HTTP:

| Código | Acción |
|---|---|
| 401 | Cierra sesión y redirige al login |
| 403 | Toast: "No tienes permisos" |
| 422 | Toast: "Datos inválidos" + errores por campo en el formulario |
| 500 | Toast: "Error del servidor" |
| Network | Toast: "Error de conexión" |

## Stores Pinia

| Store | Responsabilidad |
|---|---|
| auth | Token, usuario, login, logout, fetchUser |
| product | Lista de productos, CRUD, stock movements, loading/error/success |
| category | Lista de categorías, CRUD, loading/error/success |
| toast | Notificaciones globales con auto-dismiss |
