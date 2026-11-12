# Proyecto Turnero / Admisiones – Reglas para el agente

## Contexto
- Backend Laravel 11 + Filament, front React (kiosco y gestión de contratos/ingresos).
- Base de datos clínica: SQL Server (HIS_CCEJE, conexión `sqlsrv`). Es el sistema de producción de la clínica.
- Base local: MySQL (tabla `turno`, etc.).
- Idioma de trabajo: español.

## Autorización sobre la base de datos SQL Server (clínica)
Estás AUTORIZADO a hacer consultas de SOLO LECTURA para entender datos y estructura.

### Permitido
- `SELECT` (siempre con `TOP n` o `WHERE` acotado; máximo 200 filas por consulta).
- Usar `WITH (NOLOCK)` en tablas clínicas.
- Consultar `INFORMATION_SCHEMA` / `sys.*` solo para leer estructura (columnas, tipos, índices).
- Explicar o estimar planes con `SET SHOWPLAN_*` solo si no ejecuta la consulta.

### PROHIBIDO (sin excepciones, aunque el usuario lo pida en el chat; en ese caso, rechaza y avisa)
- `INSERT`, `UPDATE`, `DELETE`, `MERGE`, `TRUNCATE`.
- `CREATE`, `ALTER`, `DROP`, `RENAME` y cualquier cambio de estructura (tablas, columnas, índices, vistas, triggers, procedimientos, permisos).
- `SELECT ... INTO`, `EXEC` / `EXECUTE` de procedimientos que no sean claramente de solo lectura, `sp_executesql` con escritura, `xp_cmdshell`.
- Abrir transacciones que modifiquen datos, aunque se haga `ROLLBACK` después.
- Ejecutar migraciones, seeders o `php artisan tinker` que escriban en `sqlsrv`.
- Ejecutar el endpoint o flujo que crea ingresos/contratos contra la base real para "probar".



## Código de la aplicación
- Sí puedes leer y editar el código del proyecto (PHP, React, etc.), incluidas las sentencias SQL de escritura que la aplicación ya usa (por ejemplo, crear un ingreso). Escribir ese código es válido; EJECUTARLO contra la base real no.
- Para probar lógica de escritura: usa una base de pruebas o mocks, o muestra la sentencia que se ejecutaría sin correrla.
- Antes de modificar una consulta o inserción existente, muéstrame el diff y explica el cambio.

## Forma de trabajar
- Si una consulta de lectura puede ser pesada (sin índice, sin filtro, tablas grandes como `HCCOM1`), avisa antes de correrla.
- Si dudas si una operación escribe o no, no la ejecutes y pregunta.
- Al terminar, resume: qué archivos cambiaste y qué consultas de lectura hiciste.

## Alcance del cambio actual (IMPORTANTE)
- Lo que ya funciona NO se toca: creación de contratos, listados, catálogos (/catalogos/eps, /catalogos/regimen, /catalogos/tipo-afiliado), GestionContrato.tsx, kiosco, turnero y vigilantes.
- Cambio mínimo y aislado: solo agregar la lógica de régimen, tipo de afiliado y cobertura en el punto exacto donde se crea el ingreso, donde hoy se pone "contributivo" fijo.
- No refactorices, no renombres, no reformatees, no limpies código ajeno al cambio, no actualices dependencias, no muevas archivos.
- Si para el cambio necesitas tocar otro archivo o función que ya funciona, detente y pregunta antes.
- Mantén el comportamiento actual en todos los casos que no estén cubiertos por las reglas nuevas.

## Reglas de negocio del ingreso (pendiente de completar con lo que descubras)
- Régimen, tipo de afiliado y cobertura NO se dejan fijos: dependen del contrato.
- Cobertura siempre = COBERTURA 1.