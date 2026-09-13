# Auditoria de CRUD, seguridad y acceso por sucursal

Fecha: 2026-09-12

## Modelo aplicado

- `SuperAdmin`: acceso global y administracion de sucursales, categorias y usuarios.
- `Admin de sucursal`: opera solamente en sus sucursales y puede administrar empleados asignados a ellas.
- `Empleado`: opera en una unica sucursal.
- `Propietario`: consulta inventario, pedidos, ventas y reportes de sus sucursales, sin modificar datos.
- Permiso excepcional `inventory.view_all`: permite al propietario consultar inventario global sin ampliar sus permisos de escritura ni sus reportes financieros.

La autorizacion combina rol, capacidad y relacion `usuario_sucursales`. Cada endpoint vuelve a validar el permiso y la sucursal; ocultar un boton no se considera una medida de seguridad.

## Matriz CRUD

| Entidad | Crear | Consultar | Editar | Eliminar |
| --- | --- | --- | --- | --- |
| Usuarios | Si | Si, con alcance | Si, con alcance | Baja logica |
| Sucursales | Si, SuperAdmin | Si | Si, SuperAdmin | Baja logica |
| Clientes | Si | Si | Si | Baja logica |
| Categorias | Si, SuperAdmin | Si | Si, SuperAdmin | Baja logica |
| Productos | Si | Si, con alcance | Si, con alcance | Baja logica |
| Inventario | Movimiento inicial | Si, con alcance | Abastecimiento/merma | No aplica |
| Pedidos | Si | Si, con alcance | Maquina de estados | No aplica |
| Pagos | Registro contable | Si mediante pedido | Anulacion futura | No se borra |
| Ventas | Si | Si, con alcance | Reversion futura | No se borra |

Los registros transaccionales no se eliminan. Una anulacion futura debe crear movimientos compensatorios y conservar el original.

## Controles implementados

- Permisos persistidos por rol y excepciones por usuario.
- Asignacion de una o varias sucursales por cuenta.
- Proteccion contra acceso horizontal por identificadores manipulados.
- CSRF y metodos HTTP de escritura limitados a `POST`.
- Consultas preparadas, validacion de longitud, formato, enum y limites numericos.
- Venta con precios recalculados en servidor, productos duplicados consolidados, bloqueo `FOR UPDATE` y decremento condicional.
- Libro de pagos de pedidos y libro de movimientos de inventario.
- Auditoria de usuarios, catalogo, ventas, pedidos e inventario.
- Imagenes validadas por MIME, tamano y nombre aleatorio fuera de la logica del controlador.
- Bajas logicas para conservar relaciones e historial.
- Vistas administrativas con tabla de escritorio y composicion movil.

## Despliegue

1. Respaldar la base de datos del entorno.
2. Subir el codigo sin subir `.env` ni el respaldo.
3. Ejecutar `php bin/check-database.php`.
4. Consultar pendientes con `php bin/migrate.php`.
5. Aplicar con `php bin/migrate.php --run`.
6. Cerrar y volver a iniciar sesion para recargar permisos.
7. Crear o editar los propietarios desde `Usuarios` y asignar sus sucursales.
8. Marcar `Ver todo el inventario` solo en la cuenta que requiere lectura global.

## Pendiente deliberado

- La anulacion formal de ventas y pagos requiere definir reglas contables antes de implementar movimientos compensatorios.
- MFA y recuperacion de contrasena necesitan un proveedor de correo y una decision de operacion.
- La politica CSP conserva `unsafe-inline` porque todavia existen estilos y manejadores heredados en vistas antiguas; debe endurecerse despues de retirarlos.

## Referencias oficiales

- OWASP Authorization Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html
- OWASP Transaction Authorization Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Transaction_Authorization_Cheat_Sheet.html
- PHP `password_hash`: https://www.php.net/manual/en/function.password-hash.php
- MySQL locking reads: https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html
