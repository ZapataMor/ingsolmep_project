# Especificación de Requerimientos de Software

**Módulo Maestro de Equipos, Módulo de Clientes (Empresas) y Módulo de Reportes**

> **📌 Nota importante de alcance técnico**
>
> El presente documento constituye un resumen ejecutivo y una descripción de los requerimientos principales del software. Es fundamental precisar que la totalidad de los flujos operativos, reglas de negocio y especificaciones fueron presentados durante la videollamada técnica de socialización.
>
> Asimismo, para el análisis exhaustivo e integral del proyecto, se suministró las credenciales de usuario administrador de la plataforma actual. En consecuencia, el ingeniero cuenta con el acceso completo al entorno de producción para auditar, verificar y relevar en detalle cada funcionalidad, comportamiento técnico y alcance de los procesos descritos en este documento.

El presente documento actualiza la especificación funcional y técnica requerida para el desarrollo de la plataforma, incorporando los 3 módulos principales del sistema: **Módulo Maestro de Equipos**, **Módulo de Clientes (Empresas)** y **Módulo de Reportes**. Este documento servirá como guía de requerimientos para el equipo de desarrollo de software.

---

## 1. Módulo Maestro de Equipos

Funciona como el catálogo central de referencia para la parametrización global de equipos, accesorios y configuraciones base.

### 1.1 Catálogo y Filtros de Búsqueda

- **Listado General:** Tabla centralizada con el inventario maestro de equipos.
- **Filtros de Búsqueda:** Localización de equipos filtrando de manera combinada por:
  - **Descripción / Nombre:** Nombre o tipo de equipo.
  - **Marca:** Fabricante del equipo.
  - **Modelo:** Referencia específica de fábrica.

### 1.2 Gestión de Componentes y Accesorios

- **Asociación de Componentes:** Registro e individualización de partes, accesorios y consumibles asociados a cada equipo maestro para control operativo en mantenimientos.

### 1.3 Módulo de Observaciones por Defecto

- **Banco de Observaciones:** Banco de observaciones técnicas, recomendaciones y notas estandarizadas asociadas a cada equipo maestro.
- **Transferencia a Trabajo:** Las observaciones predeterminadas en este catálogo se transferirán de forma automática y directa al Módulo de Trabajo / Orden de Servicio al seleccionar el equipo.

---

## 2. Módulo de Clientes (Empresas)

Gestiona la estructura administrativa, documental, de infraestructura y de inventario propio de cada cliente registrado.

### 2.1 Directorio de Clientes

- **Listado de Clientes:** Listado general de instituciones y empresas registradas como clientes de la plataforma.

### 2.2 Panel de Acciones y Botonera por Cliente

Cada registro de cliente dispone de los siguientes accesos directos de gestión:

| Botón / Acción | Descripción Funcional |
|---|---|
| **Adjuntar PDF** | Carga y gestión de documentación general del cliente en formato PDF (contratos, RUT, certificaciones, soportes administrativos, etc.). |
| **Formación Continua** | Gestión y almacenamiento de actas, convocatorias, certificados y soportes documentales del sistema de capacitación/formación continua. |
| **Áreas de Trabajo** | Creación y parametrización de las sedes, pisos, servicios o áreas físicas del cliente (ej.: Urgencias, Quirófano, Odontología). |
| **Agregar Equipos (Maestro)** | Herramienta de selección para importar y vincular equipos individualmente desde el Equipo Maestro hacia el cliente. |
| **Equipos (Inventario Cliente)** | Vista en detalle del inventario asignado al cliente, mostrando sus estados, mantenimientos y ubicaciones exactas. |

---

## 3. Módulo de Reportes

Este módulo centraliza la generación de informes gerenciales y operativos. Todos los reportes generados contarán de forma obligatoria con la opción de exportación a hoja de cálculo de Microsoft Excel (`.xlsx`).

### 3.1 Reportes de Clientes e Inventarios

- **Reporte de Clientes:** Consolidado de datos generales, comerciales y de contacto de las empresas clientes registradas.
- **Reporte de Inventarios:** Exportación del inventario general o filtrado por cliente, incluyendo ubicación interna, serie, marca y modelo.

### 3.2 Reportes de Mantenimiento Preventivo y Correctivo

- **Criterios de Filtrado:** Filtrado explícito por rango de fechas (Fecha Inicio - Fecha Fin) y selección individual o global de clientes.
- **Reporte de Preventivos:** Informe detallado de actividades preventivas ejecutadas o programadas dentro del periodo seleccionado por cliente.
- **Reporte de Correctivos:** Informe de atenciones correctivas, fallas reportadas, repuestos utilizados y tiempos de solución dentro del periodo y cliente especificado.

### 3.3 Reportes de Equipos por Estado y Cliente

- **Clasificación por Cliente:** Informe consolidado y segmentado de equipos asignados a un cliente específico.
- **Filtros por Estado / Condición:** Generación de listados filtrados según la condición operativa o legal del equipo:
  - **Activos:** Equipos plenamente operacionales y en servicio.
  - **Inactivos:** Equipos fuera de servicio, en baja o pendientes de repuestos.
  - **En Garantía:** Equipos amparados bajo póliza de garantía del fabricante o proveedor.
  - **Otros Estados:** Otros estados operativos aplicables en el sistema.

### 3.4 Formato y Funcionalidad de Exportación

- **Exportación a Excel:** Generación nativa de archivos `.xlsx` estructurados con encabezados, columnas formateadas y celdas listas para análisis de datos.
