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
3. Definir `.env` fuera del control de versiones y restringir sus permisos.
4. Servir exclusivamente `public/` como `DocumentRoot`.
5. Habilitar HTTPS y HSTS.
6. Verificar permisos de escritura solo para `storage/` y `public/img/productos/`.
7. Ejecutar pruebas de humo de login, pedidos, inventario, ventas, usuarios y PDF.

## Responsive

La interfaz sigue un enfoque mobile-first basado en los puntos de corte de Bootstrap. Las tablas complejas conservan su estructura y usan desplazamiento horizontal en pantallas estrechas; formularios y modales usan controles de al menos 44 px y entradas de 16 px para evitar zoom involuntario en moviles.

Los anchos de referencia para validacion son 360, 390, 576, 768, 1024, 1280 y 1440 px, en orientacion vertical y horizontal cuando corresponda.
