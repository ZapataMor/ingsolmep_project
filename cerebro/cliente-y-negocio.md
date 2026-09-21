---
tags: [cliente, negocio]
actualizado: 2026-09-16
---

# Cliente y negocio

> Todo lo que hay aquí sale del código, de los commits y de lo que el usuario indicó al crear el cerebro. Lo no confirmado con el cliente va marcado `[VERIFICAR]`. Resumen en [[00-resumen]].

## 1. Quién es el cliente

**INGSOLMEP S.A.S.** — NIT 901.616.249-1 — Riohacha, La Guajira. Lema (según `config/reportes.php`): *"Ingeniería y soluciones en equipos médicos y sistemas de potencia"*. El logo tiene un rayo en la O que representa la parte de "sistemas de potencia" del negocio ([[handoff-pantalla-de-acceso-original]]).

| Persona | Rol | Fuente |
|---|---|---|
| **Mario Luis Ramírez Ospino** | Firmante de los reportes técnicos. Cargo en config: "Ingeniero electrónico". El usuario lo llama ingeniero biomédico. | `config/reportes.php` · [VERIFICAR cargo exacto] |
| **Jorge Jacome** | Ingeniero. [VERIFICAR: ¿socio, técnico, quién opera el sistema a diario?] | indicado por el usuario |
| **Luis Felipe Zapata** | Desarrollador (ZapataMor). Contacto: lfelipezapata@uniguajira.edu.co | git |

## 2. Qué hace INGSOLMEP

Presta servicio de **mantenimiento preventivo y correctivo de equipos biomédicos** a instituciones de salud (IPS, clínicas, hospitales) de La Guajira. Cada institución es una `Empresa` en el sistema; sus equipos se registran con hoja de vida (serie, registro INVIMA, clasificación de riesgo, características eléctricas) y reciben órdenes de mantenimiento con código MP/MC. Al ejecutar una orden se emite un **reporte** con encabezado de INGSOLMEP, datos del equipo, subtareas ejecutadas, estado de accesorios y firmas.

Instituciones que aparecen en los seeders de demostración (nombres y NIT reales de Riohacha) [VERIFICAR si son clientes actuales]:
- I.P.S. Salud Integral H&B
- Clínica Riohacha S.A.S.
- E.S.E. Hospital Nuestra Señora de los Remedios

## 3. Problema que resuelve

Antes había un "sistema anterior" del que se importó parte del inventario con campos en blanco (comentario en `Equipo::scopeDatosIncompletos`). [VERIFICAR: ¿Excel? ¿otro software?]. El sistema nuevo busca:
- Tener el inventario de equipos por institución con hoja de vida completa (lo exige la habilitación en salud, Resolución 3100/2019 [VERIFICAR alcance]).
- Programar el cronograma de mantenimiento y **saber qué está vencido o atascado** (panel).
- Emitir el reporte de cada mantenimiento como constancia para la institución.
- Detectar equipos fuera de servicio, garantías por vencer y novedades sin atender.

## 4. Alcance actual (lo que existe)

Ver módulos en [[00-resumen#Módulos principales]]. En resumen: empresas, equipos, mantenimientos, reportes, panel, cuenta de usuario. Un solo tipo de usuario.

## 5. Alcance pendiente o no confirmado

- Roles y permisos (ingeniero vs. técnico vs. administrativo) [VERIFICAR].
- Portal o acceso de lectura para las instituciones cliente [VERIFICAR].
- Cronograma anual de mantenimiento por institución (hoy las órdenes se crean una a una) [VERIFICAR].
- Calibración y metrología (no existe en el modelo) [VERIFICAR si INGSOLMEP la presta].
- PDF generado en servidor (hoy es "imprimir desde el navegador").
- Notificaciones (correo/WhatsApp) de órdenes vencidas [VERIFICAR].

## 6. Acuerdo comercial

[VERIFICAR] No hay información en el repo sobre contrato, cotización, plazos ni pagos. Registrar aquí cuando se conozca.

## 7. Confidencialidad ⚠️

- El repositorio remoto `github.com/ZapataMor/ingsolmep_project` es **público** (verificado 2026-09-16: la API de GitHub responde sin autenticación).
- Contiene: NIT y nombre del cliente, nombre completo del firmante, nombres y NIT de tres instituciones de salud reales (seeders), credenciales del usuario de prueba (`prueba.ingsolmep` / `ingsolmep123` en `TestUserSeeder`).
- Este cerebro añadirá más datos del cliente. Decisión pendiente del usuario: hacer privado el repo o depurar el material sensible ([[backlog-mejoras]] #2).
- No registrar en el cerebro datos de pacientes ni credenciales reales de producción.
