---
tags: [decision, reportes]
fecha: 2026-09-01
---

# El reporte no se archiva: se recompone desde la orden y se guarda la constancia

## Contexto
Módulo de reportes (commit `d4d0a8f`, 2026-09-01). Cada orden ejecutada debe tener un documento de reporte para la institución. Había que decidir si generar y almacenar un PDF o renderizarlo bajo demanda.

## Decisión
`ReporteMantenimientoController` renderiza una vista HTML imprimible (`resources/views/reportes/mantenimiento.blade.php`) que el usuario guarda como PDF desde el navegador. Solo se puede abrir si `estado === 'ejecutado'` (404 en otro caso). Al abrirlo se llama `Reporte::registrar()`, que crea o actualiza una fila en `reportes` con `generado_por`, `generado_en`, `ultima_generacion`, `veces_generado`. Esa fila es lo que lista el módulo de reportes.

## Por qué
- Sin dependencia de un generador de PDF ni almacenamiento de archivos.
- El documento siempre refleja los datos actuales de la orden y del prestador (`config/reportes.php`).
- La trazabilidad (quién lo emitió, cuántas veces) queda en base de datos.

## Consecuencias
- Si la orden se edita después de emitido, el documento cambia silenciosamente al reabrirlo; `Reporte::registrar` refresca tipo y empresa a propósito. Riesgo de que la IPS tenga una versión y el sistema muestre otra → [[backlog-mejoras]] #16.
- Enviar el reporte por correo o WhatsApp requiere un PDF real → [[backlog-mejoras]] #6.
- La calidad de impresión depende del navegador del usuario.
