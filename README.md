# Bespoke OS

Plataforma interna de Bespoke Advertising construida sobre Laravel para operar proyectos, aprobaciones y revision previa de materiales regulatorios.

## Estado actual

Esta base ya incluye:

- Laravel 13
- Breeze con stack `livewire`
- autenticacion lista
- dashboard inicial
- modulos base para `clientes`, `marcas`, `proyectos` y `tareas`

## Stack

- Laravel 13
- Livewire
- Blade
- Tailwind CSS
- SQLite local para desarrollo inicial
- Vite

## Documentacion de producto

- [PRD MVP](docs/prd-mvp.md)
- [Arquitectura Laravel](docs/architecture-laravel.md)
- [Modelo de datos V1](docs/data-model-v1.md)
- [Backlog MVP](docs/backlog-mvp.md)

## Primer arranque

```bash
composer install
npm install
php artisan migrate
composer run dev
```

## Proximo tramo sugerido

1. endurecer permisos por rol
2. convertir proyectos y tareas a flujos mas cercanos a operacion real
3. arrancar expediente cientifico y pipeline de revision de claims
