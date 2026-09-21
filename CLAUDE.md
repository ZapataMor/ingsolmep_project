# CLAUDE.md — ingsolmep_laravel

Aplicativo Laravel 13 + Livewire 4 + Flux UI de gestión de mantenimiento de equipos biomédicos para **INGSOLMEP S.A.S.** (Riohacha). El conocimiento del proyecto vive en `cerebro/` (vault de Obsidian versionado con el código). Este archivo define cómo trabajar con él.

## Antes de cualquier tarea

1. Leer `cerebro/00-resumen.md` (qué es, stack, módulos, estado actual, próximos pasos).
2. Leer `cerebro/backlog-mejoras.md` (mejoras numeradas con prioridad y estado).
3. Si la tarea toca el cliente, el alcance o el negocio, leer además `cerebro/cliente-y-negocio.md`.
4. Si la tarea toca la pantalla de acceso o el sistema de diseño (colores, tipografía), leer `cerebro/otros/handoff-pantalla-de-acceso-original.md`.

## Después de cambiar código

1. **Bitácora**: agregar (o ampliar) `cerebro/bitacora/AAAA-MM-DD.md` con la fecha de hoy: qué cambió, por qué, archivos clave, tests, pendientes. Añadir la fila en `cerebro/bitacora/README.md`.
2. **Decisión**: si hubo una decisión técnica importante (arquitectura, modelo de datos, seguridad, regla de negocio, alcance), crear `cerebro/decisiones/AAAA-MM-DD-slug.md` con **contexto → decisión → por qué → consecuencias**, y añadirla a `cerebro/decisiones/README.md`. Si se revierte una decisión, no borrar la nota: añadir "Revertida el …" y enlazar la nueva.
3. **Estado**: si cambió el estado del proyecto (módulo nuevo, nº de tests, despliegue, contrato, roles), actualizar `cerebro/00-resumen.md` (secciones *Módulos principales*, *Estado actual*, *Próximos pasos*) y su campo `actualizado`.
4. Si el cambio resuelve un `[VERIFICAR]` del cerebro, quitar la marca y dejar el dato confirmado.

## Comandos del cerebro

### "analiza con los agentes"
1. Leer cada archivo de `cerebro/agentes/*.md`.
2. Para cada agente, analizar el proyecto (código, tests, cerebro) desde ese rol, respondiendo sus "preguntas que siempre hace" y usando su formato **hallazgo → impacto → mejora propuesta → prioridad**.
3. Agregar cada mejora nueva al final de la tabla de `cerebro/backlog-mejoras.md` con el número siguiente, el agente que la propuso y la prioridad. **No renumerar** ni duplicar lo ya listado; si un hallazgo refuerza una mejora existente, anotarlo en esa fila.
4. Registrar la corrida en la bitácora del día con el resumen de hallazgos por agente.
5. Se puede acotar: "analiza con el agente qa-seguridad" o "analiza con el ingeniero biomédico".

### "implementa la mejora #N"
1. Tomar la fila #N de `cerebro/backlog-mejoras.md`; leer las notas que enlaza.
2. Implementarla con tests; correr `php artisan test` y `composer lint` / `composer types:check` (o `composer test`, que encadena los tres).
3. Marcar la fila como `✅ hecha` con enlace a la nota de bitácora (`[[AAAA-MM-DD]]`) y moverla a la sección **Hechas** del backlog.
4. Aplicar las reglas de *Después de cambiar código*.

## Convenciones del cerebro

- Markdown compatible con Obsidian; enlaces con `[[nombre-de-nota]]` (sin ruta ni extensión salvo para desambiguar `README`, p. ej. `[[decisiones/README|decisiones]]`).
- Fechas absolutas `AAAA-MM-DD`. Marcar con `[VERIFICAR]` toda suposición no confirmada con el código o con el cliente.
- El cerebro contiene datos del cliente y de sus instituciones; el repositorio remoto es **público** (ver `cerebro/cliente-y-negocio.md` §7). No agregar datos de pacientes, credenciales reales ni contratos con cifras hasta que se decida la visibilidad del repo.
- **Bóveda central de Obsidian**: `C:\Obsidian\Cerebros` (fuera de OneDrive). Ve este cerebro por `proyectos\ingsolmep_laravel`, junction creado con `mklink /J "C:\Obsidian\Cerebros\proyectos\ingsolmep_laravel" "C:\dev\ingsolmep_laravel\cerebro"`. No crear ni tocar el junction desde el repo (lo crea el usuario en cada equipo). La única copia real del cerebro es `cerebro/` en este repo.

## Convenciones del código (resumen)

- Componentes de página Livewire en `resources/views/pages/<modulo>/⚡index.blade.php` con parciales en `partials/`; nombres de propiedades, métodos, comentarios y UI en español.
- Toda cifra del panel se calcula con el mismo scope que aplica el listado al que enlaza (`?bandeja=…`), y `PanelTest` comprueba la coincidencia. Un indicador nuevo = scope + filtro + test.
- `mantenimientos.empresa_id` y `reportes.empresa_id` se copian del equipo/orden al guardar; toda escritura fuera del componente debe respetarlo.
- `Equipo.activo` (inventario) y `Equipo.estado_operativo` (servicio) son ejes distintos; los scopes del panel parten de `activos()`.
- El reporte se recompone desde la orden (`ReporteMantenimientoController`) y solo existe para órdenes `ejecutado`; `Reporte::registrar` deja la constancia.
- Umbrales y metas del panel en `config/panel.php`; datos del prestador y del firmante en `config/reportes.php` (vía `.env`).
- Calidad: Larastan, Pint, PHPUnit con SQLite en memoria; CI en GitHub Actions (PHP 8.3). Servidor local: `.claude/launch.json` (`php artisan serve --port=8123`). Datos demo: `php artisan migrate:fresh --seed`.
