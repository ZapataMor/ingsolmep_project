---
tags: [decision, modelo-datos, dominio]
fecha: 2026-09-02
---

# `activo` (inventario) y `estado_operativo` (servicio) son ejes distintos

## Contexto
Commit `912b11f` (2026-09-02), migración `2026_09_01_100011_add_estado_operativo_a_equipos`. Antes solo existía el booleano `activo`, que mezclaba "salió del inventario" con "está dañado".

## Decisión
`Equipo` tiene dos campos: `activo` (bool: sigue en el inventario de la institución) y `estado_operativo` (`operativo` | `fuera_servicio` | `dado_baja`). Comentario del modelo: *"un equipo dado de baja salió del inventario, uno fuera de servicio sigue en él y es un problema abierto en la institución"*. Los scopes del panel (`fueraDeServicio`, `garantiaPorVencer`, `sinMantenimiento`, `datosIncompletos`) parten siempre de `activos()`.

## Por qué
Un equipo parado es una alerta operativa y comercial (la IPS no puede prestar el servicio); uno dado de baja es historia. Contarlos juntos escondía el problema real.

## Consecuencias
- `dado_baja` y `activo = false` se solapan conceptualmente; hay que definir cuál manda [VERIFICAR: ¿dar de baja debería poner `activo = false` automáticamente?].
- La baja de un equipo biomédico es un acto formal (acta, motivo, fecha) que el modelo aún no captura (ver [[ingeniero-biomedico]]).
