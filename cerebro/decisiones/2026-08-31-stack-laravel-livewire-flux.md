---
tags: [decision, stack]
fecha: 2026-08-31
---

# Stack: Laravel 13 + Livewire 4 + Flux UI (starter kit oficial)

## Contexto
Arranque del proyecto el 2026-08-31 (commit `a3aad88`, "archivos subidos al servidor"). Se necesitaba un CRUD administrativo con autenticación completa, formularios largos y un panel, para un solo desarrollador que trabaja en dos computadores.

## Decisión
Partir del **Laravel Livewire Starter Kit** (`laravel/livewire-starter-kit`): Laravel 13, Fortify (2FA, passkeys, verificación de email), Livewire 4 con componentes de página de archivo único (`⚡index.blade.php`), Flux UI 2, Tailwind 4, Vite 8, Pest runner, Larastan, Pint, GitHub Actions.

## Por qué
- Todo en el servidor con PHP/Blade: sin API separada ni estado en el cliente que sincronizar.
- Flux da modales, tablas, toasts y sidebar coherentes sin escribir CSS desde cero.
- Fortify trae la seguridad de cuentas resuelta (rate limiting, 2FA) y probada.
- El desarrollador ya usa Laravel en otros proyectos (app-costeo), lo que reduce la curva.

## Consecuencias
- Cada página concentra lógica en un solo archivo grande; hay que vigilar cuándo extraer acciones/servicios (ver [[arquitecto]]).
- Se heredan `composer.json` con `name`/`description` del starter kit y el registro público activado ([[backlog-mejoras]] #3, #10).
- Dependencias recientes o en beta (`laravel/chisel`, `livewire/blaze`, `vite-plus`) [VERIFICAR estabilidad].
