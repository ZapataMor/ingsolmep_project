---
tags: [decision, panel, ux]
fecha: 2026-09-02
---

# El panel muestra "qué necesita mi atención hoy", no "cuántas cosas hay"

## Contexto
Panel principal (commit `a9031f8`, 2026-09-02), que pasó a ser la pantalla de aterrizaje en lugar de `equipos`. Un dashboard de conteos (N equipos, N empresas) no cambia ninguna decisión del ingeniero.

## Decisión
Cada bloque del panel es una situación accionable: cumplimiento del cronograma del mes vs. mes anterior y meta, órdenes vencidas con el retraso de la más antigua, equipos fuera de servicio sobre el inventario vigente, bandeja (novedad sin correctivo, garantía por vencer, sin mantenimiento, datos incompletos), cronogramas sin iniciar por institución. **Cada cifra enlaza al listado con el filtro puesto** (`?bandeja=...`) y **se calcula con el mismo scope** que aplica el listado, para que los dos números coincidan por construcción (`PanelTest` lo comprueba). Umbrales y metas en `config/panel.php` (configurables por `.env`), caché corta por bloque (300 s; 60 s para vencidas), gráficas cargadas después del primer pintado.

## Por qué
Los umbrales "son un acuerdo comercial con el cliente, no una constante del código" (comentario de `config/panel.php`). Separar cada bloque en su propia consulta con caché evita que una consulta lenta arrastre a la bandeja, que es lo que el usuario mira a diario.

## Consecuencias
- Todo nuevo indicador debe nacer como scope del modelo + filtro `bandeja` en el listado + test de coincidencia.
- No hay tabla de notificaciones ni estado "leído": el panel es una vista calculada. Si se quieren notificaciones por correo, se reutilizan los scopes ([[backlog-mejoras]] #13).
- Las variables `PANEL_*` no están en `.env.example` ([[backlog-mejoras]] #11).
