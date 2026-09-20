# Base de datos

`schema.sql` contiene la estructura base recibida del sistema. No contiene datos de usuarios, clientes ni ventas.

Todo cambio futuro de esquema debe agregarse como un archivo SQL incremental en `database/migrations/`, con el formato:

```text
YYYYMMDDHHMM_descripcion.sql
```

Cada migracion debe incluir una operacion reversible documentada, ejecutarse primero sobre un respaldo y evitar cambios destructivos sin una ventana de mantenimiento.

Las migraciones se aplican en orden lexicografico. Antes de ejecutarlas en produccion:

1. Crear un respaldo completo.
2. Probar la migracion sobre una copia de la base.
3. Confirmar que no existan relaciones huerfanas que impidan crear claves foraneas.
4. Consultar pendientes con `php bin/migrate.php`.
5. Aplicarlas con `php bin/migrate.php --run` o ejecutar el archivo desde phpMyAdmin.

El ejecutor crea `schema_migrations` para impedir que una migracion completada se repita.

La estructura consolidada mas reciente para phpMyAdmin es
`test_lolitas_db_estructura_phpmyadmin_2026-09-20.sql`. Para actualizar una
instalacion existente se deben ejecutar las migraciones pendientes en lugar de
importar nuevamente toda la estructura.
