---
tags: [moc, resumen]
actualizado: 2026-09-16
---

# 🧠 ingsolmep_laravel — Resumen

> **Punto de entrada del cerebro.** Léelo antes de cualquier tarea. Si cambia el estado del proyecto, actualiza esta nota y su campo `actualizado`.

## Qué es

Sistema web de **gestión de mantenimiento de equipos biomédicos** para **INGSOLMEP S.A.S.** (Riohacha, La Guajira), empresa que presta servicio de mantenimiento preventivo y correctivo a IPS, clínicas y hospitales. La app lleva el inventario de equipos por institución cliente, programa y ejecuta órdenes de mantenimiento, emite el reporte imprimible de cada orden ejecutada y muestra un panel con lo que necesita atención hoy.

Nombre interno del repo: `ingsolmep_laravel` (remoto: `ingsolmep_project`). Contexto del cliente en [[cliente-y-negocio]].

## Cliente

- **Ing. Mario Luis Ramírez Ospino** — INGSOLMEP S.A.S. Firma los reportes técnicos. Según `config/reportes.php` su cargo es "Ingeniero electrónico"; el usuario lo describe como ingeniero biomédico [VERIFICAR cuál va en el reporte].
- **Ing. Jorge Jacome** — INGSOLMEP S.A.S. [VERIFICAR rol: ¿socio, técnico de campo, usuario principal?]
- **Desarrollador**: Luis Felipe Zapata (ZapataMor).
- **Usuarios finales**: ingenieros biomédicos y técnicos de campo de INGSOLMEP; personal administrativo. Las IPS/clínicas son *clientes de INGSOLMEP* (entidad `Empresa`), no usuarios del sistema [VERIFICAR si se quiere un portal para ellas].

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP ≥ 8.3 (local 8.4.11) · **Laravel 13** · Fortify 1.37 (login por `username`, 2FA, passkeys, verificación de email, reset) · Livewire Starter Kit |
| Frontend | **Livewire 4** (componentes de página `⚡index.blade.php` en `resources/views/pages/`) · **Flux UI 2** · Blaze · Tailwind 4 · Alpine (vía Livewire) · tipografía IBM Plex Sans/Mono |
| Build | Vite 8 (`npm run dev` / `npm run build`) |
| BD | **MySQL** en local (`ingsolmep_db`, ver `.env`) · SQLite en memoria para tests (`phpunit.xml`) · sesión, caché y colas en base de datos |
| Calidad | PHPUnit 12 vía Pest runner (**90 tests, 252 aserciones**, verificado 2026-09-16) · Larastan (PHPStan) · Pint · GitHub Actions (`.github/workflows/tests.yml`, PHP 8.3, `composer ci:check`) |
| Idioma | `lang/es/` (auth, pagination, passwords, validation); UI y código en español |
| Repo | `github.com/ZapataMor/ingsolmep_project` — **PÚBLICO** ⚠️ (ver [[cliente-y-negocio#Confidencialidad]]) |

Servidor local: `.claude/launch.json` → `php artisan serve --port=8123`. Datos de demostración: `php artisan migrate:fresh --seed` (crea el usuario `prueba.ingsolmep` y tres instituciones de Riohacha con equipos, órdenes y panel poblado).

## Módulos principales (según el código, 2026-09-16)

```
app/
├── Models/
│   ├── Empresa        institución cliente (nombre, NIT, ciudad, contacto, WhatsApp, logo) → areas, equipos, mantenimientos
│   ├── Area           área/servicio dentro de una empresa
│   ├── Marca, Modelo  catálogo de fabricantes y modelos (creables desde el formulario de equipo)
│   ├── Equipo         hoja de vida: identificación, registro INVIMA, clasificación de riesgo (I/IIA/IIB/III),
│   │                  características eléctricas, plantilla de subtareas (15) y accesorios (15, estado B/R/M),
│   │                  `activo` (inventario) ≠ `estado_operativo` (operativo | fuera_servicio | dado_baja),
│   │                  garantía, último mantenimiento. Scopes de la bandeja del panel.
│   ├── Mantenimiento  orden MP-#### (preventivo) / MC-#### (correctivo); estados programado → en_proceso → ejecutado | cancelado;
│   │                  fecha programada/ejecución, técnico, costo, novedad detectada, subtareas y accesorios ejecutados
│   ├── Reporte        constancia RP-#### de que la orden ejecutada tiene reporte emitido (quién, cuándo, cuántas veces)
│   └── User           login por username; 2FA y passkeys de Fortify
├── Http/Controllers/ReporteMantenimientoController   documento imprimible de la orden (HTML para "guardar como PDF")
├── Actions/Fortify/*, Providers/FortifyServiceProvider (rate limiting login 5/min)
config/panel.php       metas y umbrales del panel (cumplimiento 95 %, correctivo estancado 15 d, garantía 60 d, sin mantenimiento 180 d)
config/reportes.php    datos del prestador (INGSOLMEP, NIT 901.616.249-1) y del firmante
resources/views/pages/
├── panel/             pantalla de aterrizaje: cronograma del mes vs. mes anterior, órdenes vencidas, equipos fuera de servicio,
│                      bandeja de atención (novedad sin correctivo, garantía por vencer, sin mantenimiento, datos incompletos),
│                      gráficas de cumplimiento e instituciones, tabla de reposición. Cada cifra enlaza al listado con el filtro puesto.
├── equipos/           listado (tarjetas/tabla, filtros por URL) + asistente de 5 pasos (general, técnicas, subtareas, accesorios, observaciones)
├── empresas/          CRUD de instituciones con logo
├── mantenimientos/    asignación y ejecución de órdenes, modales de detalle/eliminación, generación del reporte
├── reportes/          listado de reportes emitidos con filtros y reemisión
├── settings/          perfil, apariencia (tema claro/oscuro por cookie), seguridad (2FA, passkeys)
└── auth/              login personalizado INGSOLMEP (diagonal + electrocardiograma), registro, reset, verificación
resources/views/reportes/   plantilla del documento imprimible (encabezado, datos, subtareas, accesorios, textos, firmas, pie)
database/seeders/      TestUserSeeder, EquiposDemoSeeder, MantenimientosDemoSeeder, PanelDemoSeeder
```

**Roles**: no hay. Todo usuario autenticado ve y edita todo [VERIFICAR si se necesitan perfiles: ingeniero, técnico, administrativo].

## Estado actual (2026-09-16)

- Último commit: **2026-09-02** (`d943774`, branding en `.env.example`). 20 commits desde el 2026-08-31; el proyecto se construyó en tres días intensivos.
- Módulos funcionales: panel, equipos, empresas, mantenimientos, reportes, ajustes de cuenta. Todo en un solo usuario sin roles.
- Tests: **90 pasando, 252 aserciones** (2026-09-16). Los 6 fallos iniciales eran caché de vistas apuntando a la ruta antigua del repo; se resolvió con `php artisan view:clear`.
- Sin despliegue en producción [VERIFICAR]. Sin datos reales cargados [VERIFICAR]: los seeders son de demostración pero usan nombres y NIT de instituciones reales de Riohacha.
- El repo fue movido de `C:\Users\luisz\OneDrive\Documentos\Proyectos\ingsolmep_laravel` a `C:\Obsidian\Cerebros\proyectos\ingsolmep_laravel` (dentro de la bóveda). El symlink `public/storage` aún apunta a la ruta antigua → fotos y logos cargados no se sirven ([[backlog-mejoras]] #1). Ubicación recomendada: `C:\dev\ingsolmep_laravel` con junction desde la bóveda ([[2026-09-16-cerebro-en-el-repo]]).
- Working tree: la carpeta `Pantalla de acceso INGSOLMEP/` (handoff del diseño de login) aparece borrada pero no confirmada en git. Su README se conservó en [[handoff-pantalla-de-acceso-original]].
- Registro público de usuarios habilitado (`Features::registration()` en `config/fortify.php`, ruta `/register`) [VERIFICAR si es intencional].
- No existe README del proyecto ni documentación técnica fuera de los comentarios del código (que son muy buenos).

## Próximos pasos

1. Mover el repo a `C:\dev\ingsolmep_laravel`, crear el junction y reparar `public/storage` (`php artisan storage:link`).
2. Decidir sobre la visibilidad del repo y los datos reales en seeders ([[backlog-mejoras]] #2, #3).
3. Confirmar con Mario Luis y Jorge Jacome las preguntas `[VERIFICAR]` de esta nota y de [[cliente-y-negocio]].
4. Correr `analiza con los agentes` (ver `CLAUDE.md`) para poblar el [[backlog-mejoras]] desde los cinco roles, en especial [[ingeniero-biomedico]] (cronograma anual, calibración, hoja de vida según Resolución 3100/2019).
5. Retomar el desarrollo: roles de usuario, PDF real del reporte, cronograma anual por institución.

## Mapa del cerebro

- [[cliente-y-negocio]] — quién es el cliente, qué hace INGSOLMEP, alcance, confidencialidad.
- [[backlog-mejoras]] — mejoras numeradas con agente proponente y prioridad.
- `decisiones/` — una nota por decisión técnica (índice en [[decisiones/README|decisiones]]).
- `bitacora/` — una nota por fecha con los cambios (índice en [[bitacora/README|bitácora]]).
- `agentes/` — [[arquitecto]] · [[qa-seguridad]] · [[ux-ui]] · [[analista-negocio]] · [[ingeniero-biomedico]]
- `otros/` — material que no encaja en lo anterior: [[otros/README|índice]], [[handoff-pantalla-de-acceso-original]] (tokens de diseño y especificación del login).
