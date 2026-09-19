# Personalizaciones de productos

Las personalizaciones comerciales pertenecen a `productos`. El inventario sigue
representando existencias del producto terminado por sucursal; rellenos y
coberturas no se descuentan como insumos.

## Modelo

- `personalizacion_grupos`: tipos de seleccion, por ejemplo relleno o cobertura.
- `personalizacion_opciones`: valores reutilizables dentro de un grupo.
- `producto_personalizacion_grupos`: obligatoriedad y limite de selecciones.
- `producto_personalizacion_opciones`: recargo especifico para cada producto.
- `pedido_detalle_opciones`: copia historica de nombres y recargos elegidos.

Los precios mostrados por JavaScript son estimaciones. `OrderPricingService`
consulta nuevamente el precio base, la sucursal y los recargos antes de guardar
el pedido. El servidor no acepta importes de personalizacion enviados por el
navegador.

## Operacion

1. Al crear un pastel en Inventario, agregar sus grupos, opciones y recargos.
2. Asignar el producto a sus sucursales y guardar; todo se registra en una transaccion.
3. Para cambios posteriores, abrir Catalogo y usar el boton de opciones.
4. Verificar el producto desde Nuevo pedido seleccionando primero la sucursal.

Antes de desplegar, respaldar la base y ejecutar `php bin/migrate.php --run`.
Los pedidos anteriores permanecen compatibles aunque no tengan opciones
estructuradas.
