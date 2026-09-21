---
tags: [backlog]
actualizado: 2026-09-16
---

# Backlog de mejoras

> Lista numerada y **nunca renumerada**: el número es el identificador ("implementa la mejora #N"). Al terminar una, márcala `✅ hecha` con enlace a la nota de bitácora y muévela a **Hechas**. Prioridad: **P1** (bloquea el uso real del producto o expone al cliente) · **P2** (mejora clara, no urgente) · **P3** (deseable).
>
> Formato de cada fila: `#N | mejora | agente que la propuso | prioridad | estado`.
> Las entradas iniciales salen de la lectura del código al crear el cerebro (2026-09-16), no de una corrida de agentes; el campo "agente" indica desde qué rol nace la idea. Los agentes viven en `agentes/`.

| # | Mejora | Agente | Prioridad | Estado |
|---|---|---|---|---|
| 1 | **Reparar `public/storage`**: el symlink apunta a la ruta antigua del repo (`…\OneDrive\Documentos\Proyectos\…`), así que fotos de equipos y logos de empresas no se sirven. Correr `php artisan storage:link` tras mover el repo a `C:\dev\ingsolmep_laravel`. Documentarlo en el README para el segundo equipo. | [[arquitecto]] | P1 | pendiente |
| 2 | **Hacer privado el repositorio o depurar el material sensible**: NIT y nombres de instituciones reales en seeders, firmante, credenciales de prueba. Ver [[cliente-y-negocio#7. Confidencialidad ⚠️]]. | [[qa-seguridad]] | P1 | pendiente — decisión del usuario |
| 3 | **Desactivar el registro público** (`Features::registration()` en `config/fortify.php` y `/register`): cualquiera puede crearse una cuenta y ver todo el inventario del cliente. Crear usuarios solo desde consola o desde un admin. | [[qa-seguridad]] | P1 | pendiente |
| 4 | **Roles y permisos** (p. ej. `admin`, `ingeniero`, `tecnico`): quién puede eliminar equipos/órdenes, editar empresas, emitir reportes. Hoy todo usuario autenticado hace todo. [VERIFICAR perfiles con el cliente]. | [[qa-seguridad]] | P2 | pendiente |
| 5 | **Cronograma anual de mantenimiento preventivo por institución**: generar las órdenes del año a partir de una frecuencia por equipo (mensual, trimestral, semestral…) en vez de crearlas una a una. Es el documento que las IPS deben tener para habilitación (Res. 3100/2019). | [[ingeniero-biomedico]] | P1 | pendiente |
| 6 | **PDF generado en servidor** (dompdf/browsershot) para el reporte, con nombre de archivo `RP-00001-<equipo>.pdf`, en lugar de "guardar como PDF" desde el navegador; permite enviarlo por correo/WhatsApp a la institución. | [[ux-ui]] | P2 | pendiente |
| 7 | **Hoja de vida del equipo imprimible** (ficha técnica + historial de mantenimientos): documento distinto al reporte de orden, exigido en visitas de habilitación. | [[ingeniero-biomedico]] | P2 | pendiente |
| 8 | **Calibración / metrología**: registrar si el equipo requiere calibración, fecha de la última, vigencia del certificado y ente calibrador. No existe en el modelo. [VERIFICAR si INGSOLMEP presta este servicio]. | [[ingeniero-biomedico]] | P2 | pendiente |
| 9 | **Plantillas de subtareas por tipo de equipo**: hoy `Equipo::SUBTAREAS` y `ACCESORIOS` son listas fijas (orientadas a monitores/desfibriladores: SpO2, NIBP, palas). Un autoclave, una incubadora o un ecógrafo necesitan rutinas distintas. Convertirlas en catálogo editable por tipo de equipo. | [[ingeniero-biomedico]] | P2 | pendiente |
| 10 | **README del proyecto** con instalación (`composer setup`), servidor (`launch.json`, puerto 8123), seeders, tests, y la regla del cerebro. Además corregir `composer.json` (`name` y `description` siguen siendo del starter kit). | [[arquitecto]] | P2 | pendiente |
| 11 | **Completar `.env.example`** con las variables de `config/panel.php` (`PANEL_*`) y `config/reportes.php` (`REPORTE_*`) documentadas, para que el segundo equipo sepa que existen. | [[arquitecto]] | P3 | pendiente |
| 12 | **Historial de auditoría** (quién creó/editó/eliminó cada equipo y orden). Hoy solo `Reporte` guarda `generado_por`; las órdenes y equipos no registran usuario. | [[qa-seguridad]] | P2 | pendiente |
| 13 | **Notificaciones de órdenes vencidas y garantías por vencer** (correo diario o resumen semanal al ingeniero). El panel ya calcula los conjuntos; falta el canal. `MAIL_MAILER=log` por ahora. | [[analista-negocio]] | P3 | pendiente |
| 14 | **Portal de consulta para la institución cliente** (solo lectura: sus equipos, cronograma, reportes). Convertiría el reporte en un servicio diferenciador de INGSOLMEP. [VERIFICAR interés del cliente]. | [[analista-negocio]] | P3 | pendiente |
| 15 | **Importación masiva de equipos desde Excel** (el inventario "entró por importación del sistema anterior" con campos vacíos; una plantilla oficial con validación evitaría los datos incompletos que hoy detecta el panel). | [[ux-ui]] | P2 | pendiente |
| 16 | **Cierre formal de la orden**: separar "ejecutada" de "reportada" y bloquear la edición de una orden con reporte emitido (o versionar el reporte). Hoy se puede editar la orden después de emitir el RP y el documento cambia silenciosamente al reabrirlo (`Reporte::registrar` lo refresca). | [[qa-seguridad]] | P2 | pendiente |
| 17 | **Decidir el destino de `Pantalla de acceso INGSOLMEP/`** (borrada en el working tree, no confirmada): confirmar el borrado con `git rm` ya que el README quedó en [[handoff-pantalla-de-acceso-original]], o restaurarla. | [[arquitecto]] | P3 | pendiente — decisión del usuario |

## Hechas

| # | Mejora | Agente | Prioridad | Bitácora |
|---|---|---|---|---|
| — | — | — | — | — |
