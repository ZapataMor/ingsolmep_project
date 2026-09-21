---
tags: [decision, modelo-datos]
fecha: 2026-09-01
---

# `mantenimientos.empresa_id` se copia del equipo (denormalización)

## Contexto
Módulo de mantenimientos (commit `41f4eb1`, 2026-09-01). El listado de órdenes y el panel filtran por institución constantemente, y `Empresa::scopeCronogramaSinIniciar` necesita saber qué instituciones tienen órdenes del mes.

## Decisión
La tabla `mantenimientos` tiene `empresa_id` propio, que se **copia del equipo al guardar la orden** (`guardar()` en `pages/mantenimientos/⚡index.blade.php`: "la empresa se copia del equipo: es la que responde por la orden"). `Reporte` hace lo mismo con `empresa_id` y `tipo`.

## Por qué
Evitar un `JOIN` con `equipos` en cada listado, filtro y agregado del panel; permitir dos `whereIn` simples en `scopeCronogramaSinIniciar` en lugar de `EXISTS` correlacionados (comentario en `Empresa.php`).

## Consecuencias
- Si un equipo cambia de institución, sus órdenes históricas conservan la empresa de entonces (correcto para el historial), pero las órdenes abiertas quedarían desalineadas [VERIFICAR si el formulario de equipo las resincroniza].
- Cualquier escritura de `Mantenimiento` fuera del componente (comando, importación, job) debe recordar copiar `empresa_id`. Candidato a observer o a una acción única.
