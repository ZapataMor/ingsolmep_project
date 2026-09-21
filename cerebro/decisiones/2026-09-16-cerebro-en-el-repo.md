---
tags: [decision, proceso, obsidian]
fecha: 2026-09-16
---

# El conocimiento del proyecto vive en `cerebro/`, versionado con el código

## Contexto
El usuario trabaja en dos equipos y mantiene una bóveda central de Obsidian en `C:\Obsidian\Cerebros` que ve el cerebro de cada proyecto mediante junctions de Windows. El proyecto `ingsolmep_laravel` no tenía notas: el repositorio completo estaba copiado **dentro de la bóveda** (`C:\Obsidian\Cerebros\proyectos\ingsolmep_laravel`, con `vendor/` y `node_modules/`), movido desde `C:\Users\luisz\OneDrive\Documentos\Proyectos\ingsolmep_laravel` (el symlink `public/storage` y la caché de vistas aún apuntaban allá).

## Decisión
- Crear `cerebro/` en la raíz del repo con la estructura estándar (`00-resumen`, `cliente-y-negocio`, `backlog-mejoras`, `decisiones/`, `bitacora/`, `agentes/`, `otros/`), en Markdown compatible con Obsidian y enlaces `[[nota]]`.
- `CLAUDE.md` en la raíz define el flujo: leer resumen y backlog antes de cualquier tarea; bitácora, decisión y estado después de cambiar código; comandos "analiza con los agentes" e "implementa la mejora #N".
- La única copia real del cerebro es la del repo. La bóveda lo ve por junction `C:\Obsidian\Cerebros\proyectos\ingsolmep_laravel → <repo>\cerebro`. El junction lo crea el usuario en cada equipo; nunca desde el repo.
- Ubicación recomendada del repo: `C:\dev\ingsolmep_laravel` (fuera de OneDrive, que atraviesa junctions y duplicaría el cerebro; y fuera de la bóveda, para que Obsidian no indexe `vendor/`).

## Por qué
Los cambios de la app y las ideas del cerebro viajan juntos en cada commit y llegan al otro equipo con `git pull`. Los agentes (roles expertos) convierten el cerebro en una fuente de mejoras para la app, no solo en documentación.

## Consecuencias
- El repositorio remoto es público: el cerebro contendrá datos del cliente → [[backlog-mejoras]] #2.
- El material previo (README del handoff de diseño del login, borrado en el working tree) se conservó en [[handoff-pantalla-de-acceso-original]].
- Mientras el repo siga dentro de la bóveda, Obsidian indexará todo el proyecto; el junction no puede crearse hasta mover el repo (el nombre de carpeta está ocupado).
