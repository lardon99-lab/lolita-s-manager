# Lolita's Manager

Aplicacion web PHP para inventario, pedidos, ventas, caja y usuarios. Funciona en navegadores de escritorio y moviles; no requiere una aplicacion movil nativa.

## Requisitos

- PHP 8.0 o posterior con PDO MySQL y Fileinfo.
- MySQL 8 o compatible.
- Composer 2.
- Apache con `mod_rewrite` y soporte para `.htaccess`.
- HTTPS obligatorio en produccion.

## Instalacion local

1. Ejecutar `composer install`.
2. Copiar las variables requeridas de `.env.example` a un archivo `.env` local.
3. Configurar una cuenta MySQL exclusiva para la aplicacion con privilegios limitados sobre su base.
4. Apuntar Apache al directorio `public/`. En XAMPP tambien se admite la ruta actual porque los directorios internos estan bloqueados por `.htaccess`.
5. Abrir `http://localhost/lolita%27s-manager/public/login.php`.

El archivo `.env` no debe incluirse en Git, compartirse ni copiarse a registros. Si una credencial fue expuesta previamente, debe rotarse en MySQL.

## Comandos de calidad

```bash
composer test
composer analyse
composer check
composer audit --locked
```

`composer check` ejecuta PHPUnit y PHPStan. La auditoria de Composer requiere acceso a Packagist.

## Seguridad

- Las solicitudes mutables requieren sesion autenticada, metodo `POST` y token CSRF.
- Los roles se validan en el servidor y el acceso se limita por sucursal.
- Las sesiones usan cookies `HttpOnly`, `SameSite=Lax`, modo estricto y regeneracion tras el login.
- Los errores detallados se escriben en `storage/logs/app.log`; no se muestran al navegador.
- Las cargas aceptan solamente JPG, PNG o WebP detectados por contenido, con un limite de 5 MB.
- `app/`, `views/`, `vendor/`, `storage/`, pruebas y configuracion no son accesibles por HTTP.
- En produccion se debe activar `Secure` para cookies mediante HTTPS y configurar HSTS en el virtual host o proxy.

## Despliegue

1. Crear un respaldo consistente de MySQL.
2. Instalar con `composer install --no-dev --classmap-authoritative`.
3. Crear manualmente el archivo `.env` en la raiz del proyecto. La aplicacion no lee `.env.example` y `.env` no se sube mediante Git.
4. Servir exclusivamente `public/` como `DocumentRoot`.
5. Habilitar HTTPS y HSTS.
6. Verificar permisos de escritura solo para `storage/` y `public/img/productos/`.
7. Ejecutar pruebas de humo de login, pedidos, inventario, ventas, usuarios y PDF.

Configuracion minima del archivo `.env` del servidor:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dominio.example
APP_TIMEZONE=America/Tegucigalpa
SESSION_IDLE_TIMEOUT=5400
DB_HOST=servidor-mysql-del-proveedor
DB_PORT=3306
DB_DATABASE=nombre_base
DB_USERNAME=usuario_base
DB_PASSWORD=contrasena_base
```

Antes de probar el acceso, confirmar lo siguiente:

- El archivo se llama exactamente `.env`, sin extension `.txt`, y esta junto a `composer.json`.
- La carpeta `vendor/` fue generada con Composer y esta presente en el servidor.
- PHP es 8.0 o posterior y tiene habilitadas `pdo_mysql` y `fileinfo`.
- `storage/logs` y `storage/rate-limits` permiten escritura al proceso PHP.
- La base importada contiene todas las tablas de la aplicacion, no solamente `usuarios`.
- El host de MySQL es el indicado por el proveedor; en hosting compartido normalmente no es `127.0.0.1`.

Los errores de produccion muestran un codigo de referencia. Su detalle se busca primero en `storage/logs/app.log`; si esa carpeta no es escribible, se envia al registro de errores PHP del panel de hosting.

### Despliegue automatico en InfinityFree

El workflow `.github/workflows/deploy.yml` valida Composer, audita las dependencias, ejecuta las pruebas y publica mediante FTPS explicito cada `push` a `main`. Tambien puede iniciarse manualmente desde la pestana **Actions** de GitHub.

Configurar estos secretos en **GitHub > repositorio > Settings > Secrets and variables > Actions > New repository secret**:

| Secreto | Valor |
| --- | --- |
| `FTP_SERVER` | Host FTP mostrado en el panel de InfinityFree; normalmente `ftpupload.net`. No incluir `ftp://`. |
| `FTP_USERNAME` | Usuario FTP completo del panel, no el usuario de MySQL. |
| `FTP_PASSWORD` | Contrasena de la cuenta FTP. |
| `FTP_SERVER_DIR` | Directorio remoto terminado en `/`; normalmente `/htdocs/`. |

Antes de la primera ejecucion:

1. Conservar en el servidor el `.env` de produccion y comprobar que no sea accesible por HTTP.
2. Confirmar que `storage/` y `public/img/productos/` existen y mantienen permisos de escritura.
3. Crear un respaldo de los archivos y de MySQL.
4. Abrir **Actions > Pruebas y despliegue > Run workflow** y revisar que finalicen las pruebas y la publicacion.
5. Verificar login, dashboard, inventario y una operacion de escritura en el sitio.

El despliegue excluye credenciales, pruebas, archivos de base de datos, logs, sesiones y cargas de productos. No se usa limpieza total del servidor. Si cambia la estructura persistente de la aplicacion, esas exclusiones deben revisarse antes de desplegar.

InfinityFree admite FTPS explicito en el puerto 21, que es la configuracion utilizada. No cambiar a SFTP: el hosting gratuito no ofrece SSH/SFTP.

## Responsive

La interfaz sigue un enfoque mobile-first basado en los puntos de corte de Bootstrap. Las tablas complejas conservan su estructura y usan desplazamiento horizontal en pantallas estrechas; formularios y modales usan controles de al menos 44 px y entradas de 16 px para evitar zoom involuntario en moviles.

Los anchos de referencia para validacion son 360, 390, 576, 768, 1024, 1280 y 1440 px, en orientacion vertical y horizontal cuando corresponda.
