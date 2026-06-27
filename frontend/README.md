# Frontend Legacy Vue 2

Este proyecto representa un frontend legacy con problemas intencionales.

## Stack actual

- Vue 2
- Vue Router 3
- Axios directo en componentes
- Sin Pinia
- Sin Composition API
- Sin Tailwind
- Manejo débil de errores

## Instalación

```bash
cp .env.example .env
npm install
npm run dev
```

## Variables

```txt
VITE_API_URL=http://127.0.0.1:8000/api
```

## Nota

El objetivo del candidato es migrar a Vue 3, Composition API, Pinia, Tailwind, servicios centralizados e interceptores globales.
