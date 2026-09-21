---
tags: [decision, auth, ux]
fecha: 2026-08-31
---

# Login por `username`, no por correo

## Contexto
El diseño de la pantalla de acceso ([[handoff-pantalla-de-acceso-original]]) especifica un campo **Usuario** ("es usuario, no correo electrónico", placeholder `nombre.apellido`), sin registro, sin acceso con terceros ni "recordarme". Commit `0673ef2` (2026-08-31).

## Decisión
Añadir la columna `username` a `users` (migración `2026_08_31_000000`), configurar `'username' => 'username'` y `lowercase_usernames` en `config/fortify.php`, y hacer que el rate limiter de login use `username|ip`. El usuario de prueba es `prueba.ingsolmep`.

## Por qué
Los técnicos y el personal administrativo de INGSOLMEP no siempre tienen correo corporativo; el usuario corto es más fácil de recordar y de dictar. Además transmite identidad de sistema interno.

## Consecuencias
- El email sigue existiendo (Fortify lo usa para el reset de contraseña y la verificación); el registro público sigue pidiéndolo.
- El diseño decía "sin registro"; el código dejó `Features::registration()` activo → [[backlog-mejoras]] #3.
- El mensaje de error del diseño ("Le quedan 4 intentos antes del bloqueo") no está conectado con el rate limiter real [VERIFICAR en `pages/auth/login.blade.php`].
