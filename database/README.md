# Base de datos

El esquema actual existe en MySQL, pero el repositorio original no incluia migraciones ni un volcado de estructura verificable.

Todo cambio futuro de esquema debe agregarse como un archivo SQL incremental en `database/migrations/`, con el formato:

```text
YYYYMMDDHHMM_descripcion.sql
```

Cada migracion debe incluir una operacion reversible documentada, ejecutarse primero sobre un respaldo y evitar cambios destructivos sin una ventana de mantenimiento.
