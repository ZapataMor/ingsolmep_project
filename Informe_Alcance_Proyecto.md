# Informe de Alcance Integral — Plataforma INGSOLMEP

**Propósito de este documento:** Describir de forma completa y estructurada TODO lo que el cliente (INGSOLMEP S.A.S.) necesita para tener su plataforma operando en producción, desde lo más básico hasta lo más complejo, contrastando los requerimientos solicitados con lo que ya está construido. Este documento **no incluye precios**; sirve como insumo para elaborar la cotización.

---

## 0. Contexto del cliente y del proyecto

| Dato | Detalle |
|---|---|
| **Cliente** | INGSOLMEP S.A.S. — NIT 901.616.249-1 — Riohacha, La Guajira (Colombia) |
| **Giro** | Ingeniería y soluciones en equipos médicos y sistemas de potencia. Presta servicios de mantenimiento preventivo y correctivo de equipos biomédicos a instituciones de salud (hospitales, clínicas, consultorios). |
| **Firmante de reportes** | Mario Luis Ramírez Ospino — Ingeniero electrónico |
| **Situación actual** | El cliente **ya opera una plataforma en producción** (sistema anterior). Entregó credenciales de administrador para auditarla. La nueva app debe reemplazarla y, por tanto, **heredar sus datos**. |
| **Fuente de requerimientos** | Documento "Especificación de Requerimientos de Software" (3 módulos) + videocall técnica de socialización (flujos y reglas de negocio no documentados por escrito). |
| **Contexto regulatorio** | Equipos biomédicos en Colombia: registro INVIMA, clasificación de riesgo (I, IIA, IIB, III), hojas de vida de equipos, cronogramas de mantenimiento — exigidos por la Resolución 3100 de 2019 y el Decreto 4725 de 2005 para habilitación de IPS. Esto eleva la exigencia de trazabilidad y calidad de los reportes. |

---

## 1. Estado actual de la aplicación (lo que YA existe)

### 1.1 Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3, Laravel 13 (starter kit Livewire) |
| Frontend | Livewire 4 (componentes de página de archivo único), Flux UI 2 (versión gratuita), Tailwind CSS 4, Vite 8 |
| Autenticación | Laravel Fortify: login por usuario/email, verificación de correo, recuperación de contraseña, 2FA (TOTP + códigos de recuperación), passkeys (WebAuthn) |
| Base de datos | SQLite en desarrollo. Migraciones portables a MySQL / PostgreSQL |
| Colas / caché / sesión | Driver `database` (sin Redis) |
| Correo | Driver `log` (no hay SMTP configurado) |
| Calidad | Pest 5 (tests), Larastan nivel PHPStan (análisis estático), Pint (estilo), GitHub Actions (CI en cada push/PR) |
| Despliegue | **Ninguno.** No hay Docker de producción, ni scripts de deploy, ni servidor configurado. Solo Sail para desarrollo local. |

### 1.2 Historial de desarrollo

- 25 commits entre el **31 de agosto y el 10 de septiembre de 2026** (≈ 2 semanas de trabajo intensivo).
- Código escrito en español, con comentarios de intención de negocio, tests de comportamiento por módulo.
- Diseño de pantalla de login entregado como "design handoff" (Figma → HTML/assets) ya implementado.

### 1.3 Modelo de datos actual (8 tablas de negocio)

| Tabla | Descripción | Campos relevantes |
|---|---|---|
| `users` | Cuentas de acceso | name, username, email, rol (`administrador` / `institucion` / `tecnico`), empresa_id, telefono, activo, 2FA, passkeys |
| `empresas` | Clientes (instituciones) | nombre, nit, email, ciudad, direccion, telefono, celular, whatsapp, logo_path, activo, soft delete |
| `areas` | Áreas físicas de cada empresa | empresa_id, nombre (única por empresa) |
| `marcas` | Catálogo de fabricantes | nombre (único) |
| `modelos` | Catálogo de modelos por marca | marca_id, nombre |
| `equipos` | **Inventario del cliente** (cada equipo pertenece directamente a una empresa) | ~45 campos: descripción, serie, registro INVIMA, riesgo, especialidad, fabricante, país, adquisición, garantía_vence, prioridad, estado_operativo (operativo / fuera_servicio / dado_baja), activo, foto, 11 campos de especificaciones eléctricas/físicas, subtareas (JSON), accesorios_estado (JSON), componentes (texto libre), observaciones técnicas/generales/OT, último_mantenimiento, soft delete |
| `mantenimientos` | Órdenes de trabajo | equipo_id, empresa_id, tipo (preventivo / correctivo), estado (programado / en_proceso / ejecutado / cancelado), prioridad, fecha_programada, fecha_ejecucion, tecnico_id, motivo, descripcion, repuestos (texto), observaciones, costo, presenta_novedad, novedad, subtareas (JSON), accesorios_estado (JSON), soft delete |
| `reportes` | Registro de reportes PDF emitidos por OT | mantenimiento_id, empresa_id, tipo, generado_por, veces_generado, fechas |

### 1.4 Módulos funcionales existentes

**a) Panel principal (dashboard)**
- KPIs: cumplimiento de cronograma del mes vs. meta configurable (95 %), órdenes ejecutadas, comparativo con mes anterior.
- Bandeja de atención: órdenes vencidas, correctivos estancados (>15 días), garantías por vencer (60 días), equipos sin mantenimiento (>180 días), equipos con datos incompletos, novedades sin correctivo de seguimiento, equipos fuera de servicio, cronogramas de clientes sin iniciar.
- Gráficas: órdenes por mes, instituciones atendidas; tabla de reposición de equipos.
- Cada indicador enlaza al listado con el filtro aplicado. Caché de 60–300 s.
- Umbrales parametrizables por `.env` (`config/panel.php`).

**b) Módulo de Equipos (inventario)**
- CRUD completo con formulario tipo wizard de 5 pasos: General → Especificaciones técnicas → Subtareas de mantenimiento → Accesorios → Observaciones.
- Creación inline de área, marca y modelo desde el formulario.
- Carga de foto del equipo.
- Filtros combinados: búsqueda libre, empresa, área, marca, modelo, serie, riesgo, activo, bandeja.
- Vista en tarjetas o tabla, paginación, ordenamiento.
- Semáforo visual de días restantes al próximo mantenimiento (verde / amarillo / rojo).
- 15 subtareas de mantenimiento y 15 accesorios predefinidos como constantes en código (no editables por el usuario).

**c) Módulo de Empresas (clientes)**
- CRUD con logo, datos de contacto, enlace directo a WhatsApp, activar/desactivar.
- Ficha con pestañas: información, equipos asignados, usuarios.
- Filtros por ciudad, estado; resumen numérico.
- Solo visible para administrador.

**d) Módulo de Mantenimientos (órdenes de trabajo)**
- CRUD de órdenes preventivas y correctivas; asignación de técnico; cambio de estado; registro de ejecución con subtareas realizadas y estado de accesorios (B/R/M); novedades; costo y repuestos en texto libre.
- Al ejecutar, actualiza `ultimo_mantenimiento` del equipo.
- Código de orden: MP-00001 / MC-00001.

**e) Módulo de Reportes (actual)**
- Registro de los reportes de OT emitidos, con filtros por tipo, empresa, rango de fechas.
- Documento imprimible (HTML con estilos de impresión → el usuario lo guarda como PDF desde el navegador). Incluye encabezado con logo del prestador, datos del equipo, subtareas, accesorios, textos y firmas.
- Datos del prestador y firmante configurables por `.env`.
- **No es el módulo de reportes gerenciales que pide la especificación; no genera Excel.**

**f) Módulo de Usuarios**
- CRUD de usuarios con rol, empresa vinculada (para rol institución), teléfono, activo/inactivo.
- Solo visible para administrador.

**g) Seguridad y control de acceso**
- 3 roles con scope global de visibilidad (`Visibilidad`): la institución solo ve sus datos; el técnico solo ve lo asignado; administrador ve todo.
- Gates por capacidad: gestionar-usuarios, gestionar-empresas, gestionar-equipos, asignar-mantenimientos, gestionar-reportes.
- Middleware `activo` (bloquea usuarios desactivados).
- 2FA y passkeys disponibles en el perfil de usuario.

**h) Configuración de perfil**
- Editar perfil, cambiar contraseña, apariencia (claro/oscuro), 2FA, passkeys, eliminar cuenta.

### 1.5 Calidad actual

- **136 tests** (133 pasan, 1 falla por un detalle del menú de navegación, 2 omitidos). 399 aserciones.
- Cobertura funcional de: autenticación, empresas, equipos, mantenimientos, panel, reportes, usuarios, visibilidad por rol, perfil.
- Análisis estático (Larastan) y estilo (Pint) pasan en CI.

---

## 2. Análisis de brecha: requerimientos vs. estado actual

Leyenda: ✅ Existe · 🟡 Parcial (existe algo, hay que adaptar/extender) · ❌ No existe

### 2.1 Módulo Maestro de Equipos

| Req. | Requerimiento | Estado | Observación |
|---|---|---|---|
| 1.1 | Listado general (catálogo maestro) | ❌ | **Cambio arquitectónico.** Hoy no hay concepto de "equipo maestro" o plantilla. Cada equipo se crea directamente dentro de una empresa. Hay que crear una entidad nueva (`equipos_maestro`) que actúe como catálogo de referencia y vincular el inventario del cliente a ella. |
| 1.1 | Filtros por descripción, marca, modelo | 🟡 | Los filtros ya existen en el inventario y se pueden reutilizar, pero sobre la nueva entidad. |
| 1.2 | Componentes y accesorios individualizados por equipo maestro | 🟡 | Hoy los accesorios son una lista fija de 15 en código y "componentes" es un campo de texto libre. Se necesita una tabla `componentes` (nombre, tipo: parte/accesorio/consumible, cantidad, obligatorio) relacionada al maestro, editable desde la interfaz, que se herede al equipo del cliente y se evalúe en cada mantenimiento. |
| 1.3 | Banco de observaciones por defecto | 🟡 | Existe un solo campo `observaciones_ot` por equipo. Se necesita una tabla `observaciones_maestro` (múltiples observaciones, recomendaciones y notas estandarizadas por equipo maestro), con gestión desde la UI. |
| 1.3 | Transferencia automática a la orden de servicio | 🟡 | Ya se copian subtareas y accesorios del equipo a la orden. Hay que extender el mecanismo para que las observaciones del banco se transfieran al crear la OT y sean editables ahí. |

### 2.2 Módulo de Clientes (Empresas)

| Req. | Requerimiento | Estado | Observación |
|---|---|---|---|
| 2.1 | Directorio de clientes | ✅ | Existe con filtros, ficha, logo, contacto. Puede requerir ajustes visuales según lo hablado en la videollamada. |
| 2.2 | **Adjuntar PDF** (contratos, RUT, certificaciones) | ❌ | Nuevo submódulo: tabla `documentos_empresa`, carga de archivos PDF con validación (tipo, tamaño), almacenamiento **privado** (no accesible por URL pública), descarga con autorización, categorías, fecha de vencimiento opcional, eliminación. |
| 2.2 | **Formación Continua** (actas, convocatorias, certificados) | ❌ | Nuevo submódulo: tabla `formaciones` (tema, fecha, asistentes, tipo de soporte) + documentos adjuntos. Es similar al anterior pero con metadatos propios (fecha de capacitación, personal capacitado, tema, intensidad horaria). Posible reporte de formaciones por periodo. |
| 2.2 | **Áreas de Trabajo** (sedes, pisos, servicios) | 🟡 | Existe `areas` plana (solo nombre), creada inline desde el formulario de equipo. Se necesita gestión propia desde la ficha del cliente y, según lo hablado en la videollamada, posiblemente jerarquía (Sede → Piso → Servicio/Área). |
| 2.2 | **Agregar Equipos (Maestro)** | ❌ | Depende del módulo maestro. Selector con búsqueda que copia la plantilla al inventario del cliente, pidiendo los datos individuales (serie, área, INVIMA, fecha de adquisición, garantía, etc.). Posible carga múltiple (varias unidades del mismo maestro). |
| 2.2 | **Equipos (Inventario Cliente)** | 🟡 | Existe como pestaña en el modal de la ficha, con paginación limitada. Se necesita vista completa con estados, mantenimientos, ubicación y acciones. |

### 2.3 Módulo de Reportes (gerenciales)

| Req. | Requerimiento | Estado | Observación |
|---|---|---|---|
| 3.1 | Reporte de Clientes | ❌ | Datos existen; falta la pantalla de reporte y la exportación. |
| 3.1 | Reporte de Inventarios (general / por cliente) | ❌ | Datos existen (ubicación, serie, marca, modelo); falta reporte y exportación. |
| 3.2 | Reporte de Preventivos (rango de fechas + cliente) | ❌ | Datos existen; falta reporte con filtros y exportación. |
| 3.2 | Reporte de Correctivos (fallas, repuestos, tiempos de solución) | 🟡 | Datos existen parcialmente: repuestos es texto libre; "tiempo de solución" se puede calcular (fecha_programada → fecha_ejecucion) pero conviene registrar fecha/hora de reporte de la falla y de cierre para exactitud. |
| 3.3 | Equipos por estado y cliente (Activos, Inactivos, En garantía, Otros) | 🟡 | Los estados existen (`activo`, `estado_operativo`, `garantia_vence`), pero la clasificación "En garantía" es derivada de fecha y "Inactivos" mezcla fuera de servicio / dado de baja / pendiente de repuestos. Hay que definir la taxonomía de estados exacta con el cliente. |
| 3.4 | **Exportación a Excel (.xlsx)** obligatoria en todos los reportes | ❌ | No hay ninguna librería de Excel instalada. Se requiere integrar (p. ej. `openspout` o `maatwebsite/excel`), diseñar plantillas con encabezados, formato de columnas, anchos, fechas y números; y para volúmenes grandes, generar en cola (background job) con notificación de descarga. |

### 2.4 Requerimientos implícitos (no escritos, pero necesarios para que el sistema funcione)

| Requerimiento | Estado | Observación |
|---|---|---|
| Migración de datos del sistema anterior | ❌ | El cliente tiene datos en producción (empresas, equipos, historial de mantenimientos, usuarios, posiblemente documentos). Hay que auditar ese sistema, extraer, mapear, limpiar y cargar. El código ya menciona que "parte del inventario entró por importación del sistema anterior y trae campos en blanco". |
| Auditoría de la plataforma actual | ❌ | Explícitamente exigida en la especificación: relevar cada flujo del sistema en producción para replicarlo o mejorarlo. |
| Servidor y despliegue | ❌ | No existe nada. |
| Correo transaccional | ❌ | Necesario para recuperación de contraseña, verificación de email, notificaciones. |
| Copias de seguridad | ❌ | Crítico: los documentos PDF y el historial de mantenimientos son evidencia legal para habilitación. |
| Registro de auditoría (quién hizo qué) | ❌ | No hay bitácora de cambios. Para un sistema con evidencia regulatoria es recomendable. |
| Notificaciones (correo/WhatsApp) de mantenimientos próximos o vencidos | ❌ | No se pide explícitamente, pero el panel ya calcula la información; es una extensión natural que puede surgir. |
| Generación de PDF real en servidor | 🟡 | Hoy el PDF depende de "Imprimir → Guardar como PDF" en el navegador. Para adjuntar reportes por correo o almacenarlos, se necesita generación en servidor (Browsershot/Chromium o DomPDF). |
| Manual de usuario y capacitación | ❌ | El cliente tiene 3 perfiles de usuario (admin, técnicos, instituciones) que deben aprender la herramienta. |

---

## 3. Trabajo por realizar — de lo más básico a lo más complejo

Cada bloque indica: qué se hace, entregables y nivel de complejidad (Bajo / Medio / Alto / Muy alto).

### FASE A — Levantamiento y análisis (complejidad: Media)

1. **Auditoría de la plataforma actual en producción**
   - Recorrer con las credenciales de administrador cada pantalla, flujo, formulario, reporte y regla de negocio.
   - Inventariar campos, estados, catálogos, formatos de reportes existentes.
   - Identificar qué funcionalidades del sistema anterior NO están en la especificación escrita pero el cliente espera (riesgo de alcance oculto).
   - Entregable: documento de relevamiento funcional + capturas.
2. **Formalización de la videollamada técnica**
   - Convertir lo hablado en actas de requerimientos con reglas de negocio explícitas.
   - Definir taxonomía definitiva de estados de equipo (activo, inactivo, en garantía, comodato, baja, pendiente de repuestos, etc.).
   - Definir jerarquía de áreas (¿sede → piso → servicio?).
   - Definir categorías de documentos PDF y de formación continua.
   - Entregable: especificación funcional detallada firmada por el cliente (protege el alcance).
3. **Análisis de datos a migrar**
   - Obtener acceso a la base de datos o exportaciones del sistema anterior.
   - Perfilar volumen (cuántas empresas, equipos, mantenimientos, documentos), calidad (duplicados, campos vacíos) y formato.
   - Entregable: plan de migración y matriz de mapeo campo a campo.

### FASE B — Diseño de base de datos (complejidad: Media-Alta)

Cambios sobre el esquema existente:

1. **Nueva entidad `equipos_maestro`** (catálogo): descripción, marca_id, modelo_id, tipo/categoría, clasificación de riesgo por defecto, especificaciones técnicas por defecto, subtareas por defecto, foto de referencia, activo.
2. **Nueva tabla `componentes_maestro`**: equipo_maestro_id, nombre, tipo (parte / accesorio / consumible), cantidad, evaluable en mantenimiento (sí/no), orden.
3. **Nueva tabla `observaciones_maestro`**: equipo_maestro_id, tipo (observación / recomendación / nota), texto, orden, activo.
4. **Modificar `equipos`**: agregar `equipo_maestro_id` (nullable para los equipos ya existentes), separar los accesorios fijos de código hacia datos heredados del maestro (tabla `equipo_componentes` o JSON estructurado).
5. **Modificar `mantenimientos`**: estructurar repuestos (tabla `mantenimiento_repuestos` o JSON con nombre/cantidad/costo), agregar fecha/hora de reporte de falla y de solución para calcular tiempos; agregar observaciones transferidas del maestro.
6. **Nueva tabla `documentos`** (polimórfica o específica): empresa_id, categoría (contrato / RUT / certificación / otro), nombre, ruta privada, tamaño, mime, fecha_vencimiento, subido_por.
7. **Nueva tabla `formaciones`**: empresa_id, tema, fecha, tipo (acta / convocatoria / certificado), asistentes/observaciones, + relación con documentos.
8. **Modificar `areas`**: agregar `padre_id` (jerarquía) y `tipo` (sede / piso / servicio) si se confirma; agregar dirección/piso opcional.
9. **Modificar `equipos.estado_operativo`**: ampliar el catálogo de estados según taxonomía acordada, o crear tabla `estados_equipo` administrable.
10. **Nueva tabla `auditoria`** (bitácora): usuario, acción, modelo, id, cambios (JSON), IP, fecha.
11. **Índices** para los reportes por rango de fechas y cliente (`mantenimientos(empresa_id, tipo, fecha_programada)`, `equipos(empresa_id, estado_operativo)`).
12. **Migración a motor de producción**: pasar de SQLite a MySQL 8 o PostgreSQL 16; validar que todas las consultas (hay `whereRaw`, `SUM(cond)`, `COALESCE`) funcionen igual.
13. Entregables: diagrama entidad-relación actualizado, migraciones, factories y seeders de prueba.

### FASE C — Diseño de interfaz (frontend / UX) (complejidad: Media)

Ya existe un sistema visual (Flux UI + Tailwind, modo claro/oscuro, login con diseño propio). Se debe diseñar en coherencia con él:

1. Pantalla de catálogo maestro (listado, filtros, ficha del maestro con pestañas: datos / componentes / observaciones).
2. Formulario de equipo maestro y editores de componentes y observaciones (listas ordenables, agregar/quitar).
3. Botonera por cliente en el directorio (5 acciones) y sus 5 pantallas/modales: documentos PDF, formación continua, áreas de trabajo, agregar desde maestro, inventario del cliente.
4. Selector de equipos maestro con búsqueda y vista previa, con formulario de "individualización" (serie, área, INVIMA, garantía…).
5. Módulo de reportes gerenciales: pantalla por reporte con filtros (rango fechas, cliente, estado), vista previa en tabla, botón de exportar Excel, indicador de progreso si la exportación va en cola.
6. Ajustes al panel para reflejar los nuevos datos (documentos por vencer, formaciones).
7. Diseño responsivo (los técnicos usan celular en campo) y verificación en modo oscuro.
8. Entregables: prototipos (Figma o HTML) de cada pantalla nueva aprobados por el cliente antes de programar.

### FASE D — Desarrollo backend + frontend por módulo

#### D.1 Módulo Maestro de Equipos (complejidad: Alta)
- CRUD de equipos maestro con filtros combinados.
- Gestión de componentes y accesorios por maestro (UI de lista editable).
- Banco de observaciones por maestro.
- Lógica de herencia: al crear un equipo de cliente desde un maestro, copiar especificaciones, subtareas, componentes y observaciones (snapshot, no referencia viva, para que cambios posteriores al maestro no alteren historial).
- Transferencia automática a la OT al seleccionar el equipo.
- Refactorizar los 15 accesorios y 15 subtareas fijos en código para que vengan de datos.
- Migrar los equipos existentes: crear maestros a partir de las combinaciones marca+modelo+descripción actuales y enlazarlos.
- Tests de comportamiento para cada regla.

#### D.2 Módulo de Clientes — extensiones (complejidad: Alta, por la suma de 5 submódulos)
- **Documentos PDF:** carga (drag & drop, validación de MIME real, tamaño máximo, escaneo básico), almacenamiento en disco privado, descarga por ruta autorizada (nunca URL pública), categorías, vencimiento con alerta en panel, eliminación con confirmación, listado con filtros.
- **Formación continua:** CRUD de formaciones + adjuntos; listado por cliente y por periodo; posible reporte/exportación.
- **Áreas de trabajo:** CRUD dedicado desde la ficha del cliente, jerarquía si aplica, reasignación de equipos entre áreas, no permitir borrar área con equipos.
- **Agregar equipos desde maestro:** selector con búsqueda, individualización, alta múltiple.
- **Inventario del cliente:** vista completa (no modal), con estado, semáforo, ubicación, último y próximo mantenimiento, acceso a la hoja de vida y a las órdenes del equipo.
- Ajustar el scope de visibilidad por rol para las nuevas entidades (la institución debe ver sus propios documentos y formaciones; el técnico no).
- Tests.

#### D.3 Módulo de Reportes gerenciales con Excel (complejidad: Alta)
- Integración de librería de Excel.
- 5 reportes (clientes, inventarios, preventivos, correctivos, equipos por estado), cada uno con: consulta optimizada, filtros (fechas, cliente individual/global, estado), vista previa paginada en pantalla, exportación .xlsx con encabezados, formatos de fecha/número, anchos de columna, fila de totales cuando aplique, nombre de archivo con fecha y filtros.
- Exportación asíncrona (cola) para reportes grandes, con notificación y descarga desde la app.
- Respeto del scope por rol (una institución exporta solo lo suyo).
- Estructuración de repuestos y tiempos de solución para que el reporte de correctivos tenga datos reales.
- Opcional recomendado: exportación a PDF de los mismos reportes.
- Tests que validen contenido del Excel generado.

#### D.4 Ajustes a módulos existentes (complejidad: Media)
- Formulario de equipo: ahora nace de un maestro; rediseñar el paso 1.
- Órdenes de trabajo: incorporar observaciones transferidas, repuestos estructurados, tiempos.
- Panel: nuevos indicadores (documentos por vencer, formaciones del periodo).
- Reporte PDF de OT: incluir componentes/observaciones heredados.
- Corregir el test que actualmente falla y los 2 omitidos.

#### D.5 Funcionalidades transversales (complejidad: Media)
- Bitácora de auditoría (quién creó/modificó/eliminó qué).
- Generación de PDF en servidor (para adjuntar y almacenar reportes de OT).
- Notificaciones por correo (opcional: mantenimientos próximos, documentos por vencer, asignación de OT a técnico).
- Configuración de SMTP real para recuperación de contraseña y verificación de correo.

### FASE E — Migración de datos del sistema anterior (complejidad: Alta — es la fase con más incertidumbre)

1. Extracción: exportar desde el sistema anterior (BD directa, CSV o scraping si no hay acceso a la BD).
2. Transformación: limpieza de duplicados, normalización de marcas/modelos, mapeo de estados, completar campos obligatorios, unificar formatos de fecha.
3. Carga: scripts idempotentes (comandos Artisan) que se puedan re-ejecutar; carga de documentos/archivos si existen.
4. Validación: conteos cruzados, muestreo con el cliente, reporte de registros que no pudieron migrarse.
5. Periodo de doble operación o corte definido (fecha de congelamiento del sistema anterior).
6. Entregable: datos cargados en producción + informe de migración.

### FASE F — Pruebas (complejidad: Media)

1. **Automatizadas** (ya hay base): ampliar tests Pest a todos los módulos nuevos; mantener análisis estático y estilo en CI; objetivo ≥ 80 % de las reglas de negocio cubiertas.
2. **Pruebas de navegador (E2E)**: flujos críticos (crear maestro → vincular a cliente → programar OT → ejecutar → reporte → exportar Excel) con Laravel Dusk o Playwright.
3. **Pruebas de carga**: reportes y exportaciones con volumen realista (miles de equipos, decenas de miles de OT).
4. **Pruebas de aceptación (UAT)** con el cliente: guion de pruebas por rol, registro de hallazgos, ciclo de corrección.
5. **Pruebas de migración**: ensayo completo en staging antes del corte.
6. Entregables: suite de tests en CI, actas de UAT firmadas.

### FASE G — Seguridad (complejidad: Media-Alta)

Lo que ya existe (2FA, passkeys, roles con scope global, middleware activo, CSRF, hash de contraseñas) es una base sólida. Falta:

1. **Archivos**: validación de MIME real (no solo extensión), límite de tamaño, nombres aleatorios, disco privado fuera de `public/`, descarga por controlador con autorización, sin listado de directorios.
2. **Autorización**: revisar cada nueva ruta/acción con Gates/Policies; tests negativos (un usuario de institución intentando descargar el documento de otra).
3. **Rate limiting** en login, recuperación de contraseña, exportaciones.
4. **Cabeceras HTTP**: HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy.
5. **Dependencias**: `composer audit` / `npm audit` en CI, actualización periódica.
6. **Datos**: cifrado en tránsito (TLS), cifrado en reposo de backups, contraseñas de BD fuertes, `.env` fuera del repo, `APP_DEBUG=false`.
7. **Bitácora de auditoría** y logs de acceso.
8. **Revisión OWASP Top 10** (inyección, XSS en campos de texto libre y observaciones, IDOR en IDs de rutas, carga de archivos, configuración).
9. **Prueba de penetración básica** (herramientas automáticas + revisión manual) y corrección de hallazgos.
10. **Política de contraseñas y sesiones**: expiración, cierre de sesión en otros dispositivos, bloqueo por intentos.
11. **Protección de datos personales** (Ley 1581 de 2012 – Colombia): aviso de privacidad, tratamiento de datos de contacto de clientes y usuarios.
12. Entregable: informe de seguridad con hallazgos y correcciones.

### FASE H — Infraestructura y despliegue (complejidad: Media)

Actualmente no hay nada. Se necesita:

1. **Dominio** y DNS (¿ya tiene el cliente? ¿subdominio del sistema anterior?).
2. **Servidor**: VPS (p. ej. DigitalOcean, Hetzner, AWS Lightsail, Contabo) o PaaS (Laravel Cloud, Forge + VPS, Ploi). Dimensionamiento inicial: 2 vCPU / 4 GB RAM suficiente para decenas de usuarios concurrentes.
3. **Aprovisionamiento**: Ubuntu LTS, Nginx, PHP 8.3-FPM con extensiones, MySQL 8 / PostgreSQL 16, Redis (caché, colas, sesiones), Node solo para build, Supervisor para workers de cola, cron para el scheduler de Laravel, Chromium si se genera PDF en servidor.
4. **Certificado SSL** (Let's Encrypt) y redirección HTTPS forzada.
5. **Almacenamiento de archivos**: disco local con backups o bucket S3-compatible (DigitalOcean Spaces, Cloudflare R2, AWS S3) para PDFs, fotos y logos.
6. **Correo transaccional**: SMTP (Brevo, Mailgun, Amazon SES, Resend) con dominio verificado (SPF/DKIM).
7. **Pipeline de despliegue**: GitHub Actions → deploy automático (zero-downtime con Envoy/Deployer o Forge), con migraciones, build de assets, cachés de config/rutas/vistas, reinicio de colas.
8. **Entornos**: desarrollo local, **staging** (para UAT y ensayos de migración) y **producción**.
9. **Copias de seguridad**: BD diaria + archivos, retención 30 días, almacenadas fuera del servidor, prueba de restauración documentada (`spatie/laravel-backup`).
10. **Monitoreo**: uptime (UptimeRobot / BetterStack), errores (Sentry / Flare), logs centralizados, alertas por correo/Telegram.
11. **Ventana de puesta en producción**: plan de corte, rollback, comunicación a usuarios.
12. Entregables: infraestructura operando, documento de arquitectura y runbook de operación.

### FASE I — Escalabilidad y rendimiento (complejidad: Media)

El diseño actual es multi-tenant por `empresa_id` con scope global; escala razonablemente. Trabajo necesario:

1. Reemplazar drivers `database` de caché/cola/sesión por Redis.
2. Revisar consultas N+1 en listados nuevos (Laravel Debugbar / Telescope en staging); precarga (`with`) en todos los reportes.
3. Índices compuestos para reportes por fechas y cliente.
4. Exportaciones y PDFs en cola, con streaming para Excel grandes (no cargar todo en memoria).
5. Paginación por cursor en listados grandes; límites en selectores (búsqueda con debounce en lugar de cargar catálogos completos).
6. Almacenamiento de archivos en objeto (S3) para desacoplar del servidor y permitir escalar horizontalmente.
7. Cachés calculadas del panel ya existen; extender a reportes pesados.
8. Plan de crecimiento: separar BD del servidor de aplicación cuando se superen ~50 clientes activos o ~100k órdenes; CDN para assets.
9. Pruebas de carga con datos sintéticos (factories ya existen).
10. Entregable: informe de rendimiento con tiempos de respuesta objetivo (< 500 ms en listados, < 30 s en exportaciones grandes).

### FASE J — Documentación, capacitación y entrega (complejidad: Baja-Media)

1. Manual de usuario por rol (administrador, técnico, institución) con capturas.
2. Manual técnico: arquitectura, instalación, despliegue, variables de entorno, backups, restauración.
3. Sesiones de capacitación (presencial o virtual) por perfil; grabación.
4. Videos cortos de los flujos principales.
5. Entrega del código fuente en repositorio del cliente, credenciales de infraestructura, licencias.
6. Acta de entrega y aceptación.

### FASE K — Garantía, soporte y mantenimiento post-entrega (complejidad: Baja, pero recurrente)

1. Periodo de garantía (corrección de errores sin costo, típicamente 30–90 días).
2. Contrato de soporte mensual: atención de incidencias, actualizaciones de seguridad de Laravel/PHP/dependencias, monitoreo, backups, pequeños ajustes.
3. Costos recurrentes de terceros que el cliente asumirá: servidor, dominio, correo transaccional, almacenamiento, monitoreo, certificado (gratis con Let's Encrypt).
4. Evolutivos futuros probables: app móvil o PWA para técnicos en campo (offline), firma digital en reportes, notificaciones WhatsApp, calendario de cronograma, códigos QR en equipos para acceder a la hoja de vida, portal público de consulta para instituciones, integración con facturación.

---

## 4. Riesgos y supuestos que afectan el esfuerzo

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Requerimientos verbales (videollamada) no documentados | Alcance oculto, retrabajo | Fase A obligatoria; especificación detallada firmada antes de programar. |
| El sistema anterior tiene funciones que el cliente da por sentadas y no aparecen en la especificación | Crecimiento de alcance | Auditoría completa y lista de "paridad funcional" acordada. |
| Calidad de datos del sistema anterior (duplicados, vacíos, formatos) | Migración más larga de lo previsto | Perfilado de datos antes de cotizar; limpieza como ítem separado. |
| Sin acceso directo a la BD del sistema anterior | Extracción manual o por scraping, muy costosa | Confirmar forma de acceso antes de cotizar la migración. |
| Cambio arquitectónico (maestro ↔ inventario) sobre módulos ya construidos | Refactor de equipos, mantenimientos, reportes PDF y tests | Diseñar el modelo de datos antes de tocar código; mantener tests como red de seguridad. |
| Taxonomía de estados de equipo no definida | Reportes por estado inconsistentes | Cerrar definición con el cliente en Fase A. |
| Exigencias regulatorias (INVIMA, habilitación) sobre los formatos de reporte | Reportes con formato específico obligatorio | Recopilar formatos oficiales que usa el cliente hoy. |
| Uso en campo con conectividad limitada (técnicos) | Quejas de usabilidad | Diseño responsivo y ligero; PWA como evolutivo. |
| Cliente sin infraestructura ni experiencia técnica | Soporte de operación recae en el desarrollador | Contrato de soporte y hosting gestionado. |

**Supuestos:**
- Se mantiene el stack actual (Laravel 13 + Livewire 4 + Flux gratuito). Si se requiere Flux Pro (componentes avanzados: tablas con edición, editor de texto, calendario) tiene licencia de pago.
- El cliente provee logo, formatos de reporte oficiales, acceso al sistema anterior y disponibilidad para reuniones de validación.
- Un solo desarrollador (o equipo pequeño) con apoyo de herramientas de IA.
- Idioma único: español. Moneda: COP.

---

## 5. Variables que deben considerarse al cotizar

1. **Trabajo ya realizado** (≈ 2 semanas: 6 módulos, 8 tablas, 136 tests, autenticación completa, panel analítico, diseño de login). Decidir si se cobra como "valor entregado" o se absorbe como base.
2. **Horas estimadas por fase** (A–K) con el nivel de complejidad indicado.
3. **Perfil(es) involucrados**: análisis, diseño UI/UX, desarrollo full-stack, QA, DevOps, capacitación.
4. **Modalidad**: precio fijo por alcance cerrado vs. tiempo y materiales vs. híbrido (fijo para lo especificado + tarifa hora para cambios).
5. **Costos recurrentes de terceros** que se trasladan al cliente (servidor ≈ USD 12–40/mes, dominio, correo transaccional, almacenamiento S3, monitoreo).
6. **Soporte mensual** post-entrega (porcentaje del valor del proyecto o tarifa plana).
7. **Garantía** incluida y su duración.
8. **Licencias** (Flux Pro si se necesita, herramientas de PDF).
9. **Contingencia** por riesgos de alcance oculto y migración (recomendable 15–25 %).
10. **Contexto de mercado**: tarifas de desarrollo Laravel en Colombia (freelance vs. agencia), tamaño de la empresa cliente (PYME regional en La Guajira), sector salud regulado.
11. **Propiedad intelectual**: si el código se entrega al cliente o se licencia (SaaS), cambia el modelo de precio.
12. **Hitos de pago** sugeridos: anticipo al inicio, entrega de módulos, puesta en producción, cierre de garantía.

---

## 6. Resumen de esfuerzo relativo por bloque (sin valores monetarios)

| Bloque | Complejidad | Peso relativo aprox. del total | Estado |
|---|---|---|---|
| Base ya construida (auth, panel, equipos, empresas, mantenimientos, reportes PDF, usuarios, roles, tests) | — | ≈ 30–35 % del producto final | ✅ Hecho |
| A. Levantamiento y auditoría del sistema actual | Media | 5–8 % | Pendiente |
| B. Diseño de base de datos (nuevas entidades + refactor) | Media-Alta | 5 % | Pendiente |
| C. Diseño UI/UX de pantallas nuevas | Media | 5–7 % | Pendiente |
| D.1 Módulo Maestro de Equipos | Alta | 10–12 % | Pendiente |
| D.2 Extensiones de Clientes (5 submódulos) | Alta | 12–15 % | Pendiente |
| D.3 Reportes gerenciales + Excel | Alta | 10–12 % | Pendiente |
| D.4–D.5 Ajustes y transversales (auditoría, PDF servidor, correo) | Media | 5–7 % | Pendiente |
| E. Migración de datos | Alta / incierta | 5–15 % | Pendiente |
| F. Pruebas (automatizadas, E2E, carga, UAT) | Media | 6–8 % | Parcial |
| G. Seguridad (hardening + pentest básico) | Media-Alta | 4–6 % | Parcial |
| H. Infraestructura y despliegue (staging + producción, backups, monitoreo) | Media | 5–7 % | Pendiente |
| I. Escalabilidad y rendimiento | Media | 3–4 % | Pendiente |
| J. Documentación y capacitación | Baja-Media | 3–5 % | Pendiente |
| K. Soporte post-entrega | Baja (recurrente) | Mensual, aparte | Pendiente |

---

## 7. Lista de verificación de entregables finales

- [ ] Especificación funcional detallada aprobada
- [ ] Documento de auditoría del sistema anterior
- [ ] Diagrama entidad-relación y migraciones
- [ ] Prototipos de UI aprobados
- [ ] Módulo Maestro de Equipos (catálogo, componentes, observaciones, transferencia a OT)
- [ ] Módulo de Clientes con botonera completa (PDF, formación, áreas, agregar maestro, inventario)
- [ ] Módulo de Reportes gerenciales (5 reportes) con exportación Excel
- [ ] Bitácora de auditoría
- [ ] Generación de PDF en servidor y correo transaccional
- [ ] Migración de datos completada y validada
- [ ] Suite de pruebas automatizadas en CI (verde)
- [ ] Pruebas E2E de flujos críticos
- [ ] Informe de seguridad y correcciones
- [ ] Staging y producción desplegados con SSL, backups, monitoreo
- [ ] Pipeline de despliegue automático
- [ ] Manuales de usuario y técnico
- [ ] Capacitación por rol realizada
- [ ] Acta de entrega y periodo de garantía iniciado
- [ ] Contrato de soporte (opcional)
