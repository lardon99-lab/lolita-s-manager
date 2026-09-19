-- MySQL dump 10.13  Distrib 9.6.0, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: test_lolitas_db
-- ------------------------------------------------------
-- Server version	9.6.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria` (
  `id_auditoria` bigint NOT NULL AUTO_INCREMENT,
  `id_usuario` int DEFAULT NULL,
  `accion` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_id` bigint DEFAULT NULL,
  `id_sucursal` int DEFAULT NULL,
  `detalles` json DEFAULT NULL,
  `direccion_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_auditoria_entidad` (`entidad`,`entidad_id`,`fecha_registro`),
  KEY `idx_auditoria_usuario_fecha` (`id_usuario`,`fecha_registro`),
  KEY `fk_auditoria_sucursal` (`id_sucursal`),
  CONSTRAINT `fk_auditoria_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`) ON DELETE SET NULL,
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria`
--

/*!40000 ALTER TABLE `auditoria` DISABLE KEYS */;
INSERT INTO `auditoria` (`id_auditoria`, `id_usuario`, `accion`, `entidad`, `entidad_id`, `id_sucursal`, `detalles`, `direccion_ip`, `fecha_registro`) VALUES (1,1,'create','usuario',7,2,'{\"role_id\": 2, \"branches\": [2]}','::1','2026-09-13 01:39:29'),(2,1,'product.created','productos',18,NULL,'{\"sucursales\": [1, 2]}','::1','2026-09-19 16:19:39'),(3,1,'catalog.estado_producto','productos',18,NULL,NULL,'::1','2026-09-19 16:45:51'),(4,1,'catalog.estado_producto','productos',18,NULL,NULL,'::1','2026-09-19 16:45:57'),(5,1,'catalog.estado_producto','productos',18,NULL,NULL,'::1','2026-09-19 16:46:02'),(6,1,'product.created','productos',20,NULL,'{\"sucursales\": [1, 2]}','::1','2026-09-19 16:49:03'),(7,1,'create','pedido',21,1,'{\"paid\": 0, \"total\": 469}','::1','2026-09-19 16:51:40'),(8,1,'inventory.restocked','inventario',60,1,'{\"cantidad\": 10}','::1','2026-09-19 17:01:57'),(9,1,'sale.created','ventas_directas',27,1,'{\"total\": 75, \"productos\": 1, \"metodo_pago\": \"Efectivo\"}','::1','2026-09-19 17:03:14'),(10,1,'sale.created','ventas_directas',28,1,'{\"total\": 730, \"productos\": 1, \"metodo_pago\": \"Efectivo\"}','::1','2026-09-19 17:08:02'),(11,1,'update','usuario',7,2,'{\"status\": \"Activo\", \"role_id\": 2, \"branches\": [2]}','::1','2026-09-19 17:08:54'),(12,1,'catalog.actualizar_producto','productos',11,NULL,NULL,'::1','2026-09-19 17:09:29'),(13,1,'catalog.actualizar_producto','productos',5,NULL,NULL,'::1','2026-09-19 17:13:29');
/*!40000 ALTER TABLE `auditoria` ENABLE KEYS */;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `uq_categorias_nombre` (`nombre_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` (`id_categoria`, `nombre_categoria`, `estado`) VALUES (1,'Pasteles de Vitrina','Activo'),(2,'Repostería Fina','Activo'),(3,'Bebidas y Cafetería','Activo'),(4,'Panaderia','Activo'),(6,'pastel rico','Activo'),(7,'pastel','Activo');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id_cliente`),
  KEY `idx_clientes_estado_nombre` (`estado`,`nombre_completo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` (`id_cliente`, `nombre_completo`, `telefono`, `email`, `estado`) VALUES (1,'Ana Martínez','555-0123','ana.mtz@email.com','Activo'),(2,'Carlos Antonio Pineda','98331468','','Activo');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;

--
-- Table structure for table `inventario`
--

DROP TABLE IF EXISTS `inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario` (
  `id_inventario` int NOT NULL AUTO_INCREMENT,
  `id_sucursal` int NOT NULL,
  `id_producto` int NOT NULL,
  `stock_actual` int DEFAULT '0',
  `stock_minimo` int DEFAULT '5',
  `ultima_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_caducidad` date DEFAULT NULL,
  PRIMARY KEY (`id_inventario`),
  UNIQUE KEY `uq_sucursal_producto_fecha` (`id_sucursal`,`id_producto`,`fecha_caducidad`),
  KEY `id_producto` (`id_producto`),
  KEY `idx_fk_sucursal` (`id_sucursal`),
  KEY `idx_inventario_sucursal_caducidad` (`id_sucursal`,`fecha_caducidad`),
  CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`) ON DELETE CASCADE,
  CONSTRAINT `inventario_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventario`
--

/*!40000 ALTER TABLE `inventario` DISABLE KEYS */;
INSERT INTO `inventario` (`id_inventario`, `id_sucursal`, `id_producto`, `stock_actual`, `stock_minimo`, `ultima_actualizacion`, `fecha_caducidad`) VALUES (1,1,1,13,3,'2026-08-04 15:51:20',NULL),(2,1,2,16,5,'2026-06-20 16:24:28',NULL),(3,1,3,10,5,'2026-05-26 03:29:14',NULL),(4,1,5,31,10,'2026-07-13 20:46:57',NULL),(16,2,1,4,3,'2026-05-26 03:54:51',NULL),(17,2,2,5,5,'2026-07-14 16:38:52',NULL),(18,2,4,7,10,'2026-07-13 20:47:05',NULL),(19,1,6,0,5,'2026-08-04 15:51:39',NULL),(20,2,6,15,5,'2026-06-20 16:24:04',NULL),(21,1,7,3,5,'2026-08-04 15:50:09',NULL),(22,2,7,15,5,'2026-05-23 17:12:24',NULL),(23,1,8,0,5,'2026-06-20 16:05:21','2026-05-24'),(24,2,8,0,5,'2026-06-20 16:06:51','2026-05-24'),(25,1,9,10,5,'2026-05-23 17:28:39',NULL),(26,2,9,9,5,'2026-07-13 20:47:05',NULL),(27,1,10,0,5,'2026-05-26 00:21:11',NULL),(28,2,10,0,5,'2026-06-20 16:14:01','2026-05-30'),(29,1,11,12,5,'2026-09-19 17:03:14',NULL),(30,2,11,0,5,'2026-05-26 01:07:10',NULL),(31,1,12,0,5,'2026-05-26 01:17:19',NULL),(32,2,12,0,5,'2026-05-26 01:17:19',NULL),(33,1,13,0,5,'2026-05-26 03:34:35',NULL),(34,2,13,0,5,'2026-06-20 16:08:50','2026-05-26'),(35,2,8,0,5,'2026-06-20 16:24:52','2026-06-16'),(36,1,8,0,5,'2026-07-13 20:45:52','2026-06-16'),(37,1,8,0,5,'2026-07-13 20:45:55','2026-06-17'),(38,2,8,0,5,'2026-06-20 16:08:58','2026-06-17'),(39,2,8,0,5,'2026-06-25 14:50:17','2026-06-21'),(40,1,14,0,5,'2026-06-25 03:51:33',NULL),(41,2,14,0,5,'2026-06-25 03:51:33',NULL),(42,1,15,1,5,'2026-06-25 03:53:41',NULL),(43,2,15,1,5,'2026-06-25 03:53:41',NULL),(44,1,14,0,5,'2026-07-13 20:46:00','2026-07-05'),(45,2,15,0,5,'2026-07-13 20:46:02','2026-07-05'),(46,2,8,0,5,'2026-07-13 20:45:57','2026-06-26'),(47,2,8,0,5,'2026-08-04 15:09:49','2026-08-02'),(48,2,15,0,5,'2026-08-28 03:05:13','2026-08-11'),(49,2,8,0,5,'2026-08-28 03:05:20','2026-08-05'),(50,1,16,0,5,'2026-08-04 15:21:13',NULL),(51,2,16,0,5,'2026-08-04 15:21:13',NULL),(52,1,13,0,5,'2026-08-28 03:05:16','2026-08-05'),(53,1,17,5,5,'2026-08-04 15:49:07',NULL),(54,2,17,5,5,'2026-08-04 15:49:07',NULL),(55,1,18,0,5,'2026-09-19 16:19:39',NULL),(56,2,18,0,5,'2026-09-19 16:19:39',NULL),(58,1,20,0,5,'2026-09-19 16:49:03',NULL),(59,2,20,0,5,'2026-09-19 16:49:03',NULL),(60,1,20,8,5,'2026-09-19 17:08:02','2026-09-24');
/*!40000 ALTER TABLE `inventario` ENABLE KEYS */;

--
-- Table structure for table `mermas`
--

DROP TABLE IF EXISTS `mermas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mermas` (
  `id_merma` int NOT NULL AUTO_INCREMENT,
  `id_inventario` int NOT NULL,
  `id_usuario` int NOT NULL,
  `cantidad` int NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_merma`),
  KEY `id_inventario` (`id_inventario`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `mermas_ibfk_1` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id_inventario`) ON DELETE CASCADE,
  CONSTRAINT `mermas_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mermas`
--

/*!40000 ALTER TABLE `mermas` DISABLE KEYS */;
INSERT INTO `mermas` (`id_merma`, `id_inventario`, `id_usuario`, `cantidad`, `motivo`, `fecha_registro`) VALUES (1,3,1,15,'Caducidad','2026-05-23 16:54:57'),(2,18,1,10,'Caducidad','2026-05-23 17:29:06'),(3,4,1,10,'Extravío','2026-05-23 17:29:30'),(4,29,1,5,'Daño/Rotura','2026-05-26 01:15:36'),(5,23,1,3,'Producto Caducado','2026-06-20 16:05:21'),(6,24,1,15,'Producto Caducado','2026-06-20 16:06:51'),(7,34,1,5,'Producto Caducado','2026-06-20 16:08:50'),(8,38,1,10,'Producto Caducado','2026-06-20 16:08:58'),(9,28,1,10,'Producto Caducado','2026-06-20 16:14:01'),(10,35,1,10,'Producto Caducado','2026-06-20 16:24:52'),(11,39,1,15,'Producto Caducado','2026-06-25 14:50:17'),(12,36,1,20,'Producto Caducado','2026-07-13 20:45:52'),(13,37,1,10,'Producto Caducado','2026-07-13 20:45:55'),(14,46,1,10,'Producto Caducado','2026-07-13 20:45:57'),(15,44,1,10,'Producto Caducado','2026-07-13 20:46:00'),(16,45,1,10,'Producto Caducado','2026-07-13 20:46:02'),(17,29,1,5,'Caducidad','2026-07-14 16:26:42'),(18,1,1,5,'Daño/Rotura','2026-08-01 15:25:21'),(19,47,1,10,'Producto Caducado','2026-08-04 15:09:49'),(20,21,1,6,'Daño/Rotura','2026-08-04 15:50:09'),(21,48,1,5,'Producto Caducado','2026-08-28 03:05:13'),(22,52,1,5,'Producto Caducado','2026-08-28 03:05:16'),(23,49,1,10,'Producto Caducado','2026-08-28 03:05:20');
/*!40000 ALTER TABLE `mermas` ENABLE KEYS */;

--
-- Table structure for table `mermas_caja`
--

DROP TABLE IF EXISTS `mermas_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mermas_caja` (
  `id_merma` int NOT NULL AUTO_INCREMENT,
  `monto` decimal(10,2) NOT NULL,
  `motivo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `id_sucursal` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_merma`),
  KEY `idx_mermas_caja_sucursal_fecha` (`id_sucursal`,`fecha_registro`),
  KEY `idx_mermas_caja_usuario` (`id_usuario`),
  CONSTRAINT `fk_mermas_caja_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`),
  CONSTRAINT `fk_mermas_caja_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mermas_caja`
--

/*!40000 ALTER TABLE `mermas_caja` DISABLE KEYS */;
INSERT INTO `mermas_caja` (`id_merma`, `monto`, `motivo`, `descripcion`, `id_sucursal`, `id_usuario`, `fecha_registro`) VALUES (1,62.00,'Pagos a Terceros','',1,2,'2026-06-13 16:13:46'),(2,10.00,'Pago a Proveedores','',1,2,'2026-06-13 16:20:06'),(3,10.00,'Pago a Proveedores','',1,2,'2026-06-13 16:24:48'),(4,68.50,'Gastos de Insumos','',1,1,'2026-06-13 16:29:37'),(5,5.00,'Gastos de Insumos','bolsas para basura\r\n',2,3,'2026-06-13 16:47:39'),(6,3.00,'Pago a Proveedores','tres pesos',2,1,'2026-06-13 17:14:10'),(7,5.00,'Pagos a Terceros','5 pesos\r\n',1,1,'2026-06-13 17:31:35'),(8,59.00,'Gastos de Insumos','Gasolina de milo',1,2,'2026-06-17 01:50:39'),(9,5.00,'Pago a Proveedores','',1,1,'2026-06-25 14:49:35'),(10,5.00,'Gastos de Insumos','',2,1,'2026-06-25 14:49:52'),(11,100.00,'Pago a Proveedores','',1,1,'2026-07-13 20:44:56'),(12,5.00,'Gastos de Insumos','',2,1,'2026-07-14 16:39:02'),(13,15.00,'Gastos de Insumos','',2,1,'2026-08-01 15:26:08'),(14,5.00,'Pago a Proveedores','',1,1,'2026-08-04 15:25:51'),(15,5.00,'Pago a Proveedores','',1,1,'2026-09-19 18:20:39');
/*!40000 ALTER TABLE `mermas_caja` ENABLE KEYS */;

--
-- Table structure for table `movimientos_inventario`
--

DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_inventario` (
  `id_movimiento` bigint NOT NULL AUTO_INCREMENT,
  `id_inventario` int NOT NULL,
  `id_usuario` int NOT NULL,
  `tipo` enum('Inventario inicial','Abastecimiento','Merma','Venta','Reversion') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL,
  `stock_anterior` int NOT NULL,
  `stock_posterior` int NOT NULL,
  `referencia_tipo` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia_id` bigint DEFAULT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_movimientos_inventario_fecha` (`id_inventario`,`fecha_registro`),
  KEY `idx_movimientos_usuario` (`id_usuario`),
  CONSTRAINT `fk_movimientos_inventario` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id_inventario`),
  CONSTRAINT `fk_movimientos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_inventario`
--

/*!40000 ALTER TABLE `movimientos_inventario` DISABLE KEYS */;
INSERT INTO `movimientos_inventario` (`id_movimiento`, `id_inventario`, `id_usuario`, `tipo`, `cantidad`, `stock_anterior`, `stock_posterior`, `referencia_tipo`, `referencia_id`, `motivo`, `fecha_registro`) VALUES (1,55,1,'Inventario inicial',0,0,0,'productos',18,NULL,'2026-09-19 16:19:39'),(2,56,1,'Inventario inicial',0,0,0,'productos',18,NULL,'2026-09-19 16:19:39'),(3,58,1,'Inventario inicial',0,0,0,'productos',20,NULL,'2026-09-19 16:49:03'),(4,59,1,'Inventario inicial',0,0,0,'productos',20,NULL,'2026-09-19 16:49:03'),(5,60,1,'Abastecimiento',10,0,10,NULL,NULL,NULL,'2026-09-19 17:01:57'),(6,29,1,'Venta',-3,15,12,'ventas_directas',27,NULL,'2026-09-19 17:03:14'),(7,60,1,'Venta',-2,10,8,'ventas_directas',28,NULL,'2026-09-19 17:08:02');
/*!40000 ALTER TABLE `movimientos_inventario` ENABLE KEYS */;

--
-- Table structure for table `pagos_pedido`
--

DROP TABLE IF EXISTS `pagos_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos_pedido` (
  `id_pago` bigint NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_usuario` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Transferencia','Tarjeta','Otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('Aplicado','Anulado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aplicado',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pago`),
  KEY `idx_pagos_pedido_fecha` (`id_pedido`,`fecha_registro`),
  KEY `fk_pagos_usuario` (`id_usuario`),
  CONSTRAINT `fk_pagos_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`),
  CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_pedido`
--

/*!40000 ALTER TABLE `pagos_pedido` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos_pedido` ENABLE KEYS */;

--
-- Table structure for table `pedido_detalle_opciones`
--

DROP TABLE IF EXISTS `pedido_detalle_opciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_detalle_opciones` (
  `id_detalle_opcion` bigint NOT NULL AUTO_INCREMENT,
  `id_detalle` int NOT NULL,
  `id_opcion` int DEFAULT NULL,
  `grupo_nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `opcion_nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recargo_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_detalle_opcion`),
  KEY `idx_pedido_detalle_opciones_detalle` (`id_detalle`),
  KEY `fk_pedido_detalle_opciones_opcion` (`id_opcion`),
  CONSTRAINT `fk_pedido_detalle_opciones_detalle` FOREIGN KEY (`id_detalle`) REFERENCES `pedido_detalles` (`id_detalle`) ON DELETE CASCADE,
  CONSTRAINT `fk_pedido_detalle_opciones_opcion` FOREIGN KEY (`id_opcion`) REFERENCES `personalizacion_opciones` (`id_opcion`) ON DELETE SET NULL,
  CONSTRAINT `chk_pedido_detalle_opciones_recargo` CHECK ((`recargo_unitario` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_detalle_opciones`
--

/*!40000 ALTER TABLE `pedido_detalle_opciones` DISABLE KEYS */;
INSERT INTO `pedido_detalle_opciones` (`id_detalle_opcion`, `id_detalle`, `id_opcion`, `grupo_nombre`, `opcion_nombre`, `recargo_unitario`) VALUES (1,22,7,'Relleno','Dulce de leche',52.00),(2,22,10,'Cobertura','Topin',52.00);
/*!40000 ALTER TABLE `pedido_detalle_opciones` ENABLE KEYS */;

--
-- Table structure for table `pedido_detalles`
--

DROP TABLE IF EXISTS `pedido_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_detalles` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `detalles_personalizacion` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `id_pedido` (`id_pedido`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `pedido_detalles_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `pedido_detalles_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_detalles`
--

/*!40000 ALTER TABLE `pedido_detalles` DISABLE KEYS */;
INSERT INTO `pedido_detalles` (`id_detalle`, `id_pedido`, `id_producto`, `cantidad`, `precio_unitario`, `detalles_personalizacion`, `subtotal`) VALUES (1,1,1,1,25.00,'que diga feliz cumpleaños abuela',25.00),(2,3,6,50,25.00,'',1250.00),(3,4,1,1,25.00,'Feliz cumpleaños Jose Carlos, relleno de dulde, tortas de vainilla y fresa ',25.00),(4,5,1,1,25.00,'',25.00),(5,6,1,1,25.00,'que diga feliz cumpleaños abuela',25.00),(6,7,2,1,30.00,'',30.00),(7,8,1,1,25.00,'',25.00),(8,9,1,1,25.00,'',25.00),(9,10,1,2,25.00,'',50.00),(10,11,8,1,25.00,'',25.00),(11,12,1,1,175.00,'FC Carlos, relleno de dulce, torta de fresa.',175.00),(12,13,1,1,75.00,'FC Ana, torta de chocolate',75.00),(13,13,4,1,3.50,'',3.50),(14,14,2,1,30.00,'',30.00),(15,15,1,1,25.00,'hola',25.00),(16,16,14,1,220.00,'',220.00),(17,16,15,1,280.00,'',280.00),(18,17,14,1,345.00,'[Masa: Chocolate | Relleno: Dulce de Leche | Cubierta: Crema Chantilly]\r\n',345.00),(19,18,1,1,150.00,'[Masa: Chocolate | Relleno: Dulce de Leche | Cubierta: Crema Chantilly]\r\n',150.00),(20,19,16,1,485.00,'[Masa: Chocolate | Relleno: Dulce de Leche | Cubierta: Crema Chantilly]\r\n',485.00),(21,20,17,1,5125.00,'[Masa: Chocolate | Relleno: Dulce de Leche | Cubierta: Crema Chantilly]\r\n',5125.00),(22,21,20,1,469.00,'',469.00);
/*!40000 ALTER TABLE `pedido_detalles` ENABLE KEYS */;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id_pedido` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_sucursal` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_entrega` datetime NOT NULL,
  `estado` enum('Pendiente','En Preparación','Listo','Entregado','Cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente',
  `total_pedido` decimal(10,2) DEFAULT '0.00',
  `observaciones_generales` text COLLATE utf8mb4_unicode_ci,
  `monto_abonado` decimal(10,2) DEFAULT '0.00',
  `saldo_pendiente` decimal(10,2) DEFAULT '0.00',
  `estado_pago` enum('Pendiente','Abonado','Pagado') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente',
  PRIMARY KEY (`id_pedido`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_usuario` (`id_usuario`),
  KEY `idx_pedidos_sucursal_estado_entrega` (`id_sucursal`,`estado`,`fecha_entrega`),
  KEY `idx_pedidos_sucursal_registro` (`id_sucursal`,`fecha_registro`),
  CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`),
  CONSTRAINT `pedidos_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` (`id_pedido`, `id_cliente`, `id_sucursal`, `id_usuario`, `fecha_registro`, `fecha_entrega`, `estado`, `total_pedido`, `observaciones_generales`, `monto_abonado`, `saldo_pendiente`, `estado_pago`) VALUES (1,2,2,1,'2026-05-16 22:22:59','2026-05-16 18:23:00','Entregado',25.00,'',25.00,0.00,'Pagado'),(2,1,2,1,'2026-05-16 22:26:37','2026-05-16 16:26:00','Entregado',0.00,'',0.00,0.00,'Pagado'),(3,2,2,1,'2026-05-16 22:26:59','2026-05-16 16:26:00','Entregado',1250.00,'',1250.00,0.00,'Pagado'),(4,2,1,2,'2026-05-20 00:52:28','2026-05-19 18:51:00','Entregado',25.00,'',25.00,0.00,'Pagado'),(5,2,2,3,'2026-08-01 15:46:19','2026-05-19 18:56:00','Entregado',25.00,'',25.00,0.00,'Pagado'),(6,1,2,1,'2026-05-20 01:00:00','2026-05-19 18:59:00','Listo',25.00,'',12.50,12.50,'Abonado'),(7,1,1,1,'2026-05-20 01:01:15','2026-05-27 19:01:00','Entregado',30.00,'',0.00,30.00,'Pendiente'),(8,1,1,1,'2026-05-20 02:39:40','2026-05-21 20:39:00','Entregado',25.00,'',25.00,0.00,'Pagado'),(9,1,1,1,'2026-05-20 02:51:08','2026-05-19 20:51:00','Entregado',25.00,'',25.00,0.00,'Pagado'),(10,1,2,1,'2026-07-14 16:38:31','2026-05-23 14:02:00','Entregado',50.00,'',50.00,0.00,'Pagado'),(11,1,1,1,'2026-05-23 17:32:23','2026-05-23 15:35:00','Pendiente',25.00,'',12.50,12.50,'Abonado'),(12,2,1,1,'2026-05-26 00:56:20','2026-05-25 22:59:00','Pendiente',175.00,'',87.50,87.50,'Abonado'),(13,1,2,1,'2026-05-26 00:59:37','2026-05-25 19:00:00','Pendiente',78.50,'',0.00,78.50,'Pendiente'),(14,1,1,1,'2026-05-26 01:28:07','2026-05-25 23:31:00','Pendiente',30.00,'',15.00,15.00,'Abonado'),(15,1,1,1,'2026-05-26 01:29:13','2026-05-25 22:31:00','Pendiente',25.00,'',0.00,25.00,'Pendiente'),(16,1,1,1,'2026-06-25 04:05:05','2026-06-24 22:05:00','Entregado',500.00,'',500.00,0.00,'Pagado'),(17,2,2,1,'2026-06-25 04:20:30','2026-06-24 22:20:00','Entregado',345.00,'',345.00,0.00,'Pagado'),(18,2,1,1,'2026-07-13 20:25:54','2026-07-13 14:25:00','Pendiente',150.00,'',0.00,150.00,'Pendiente'),(19,1,1,1,'2026-08-04 15:23:21','2026-08-04 12:25:00','Pendiente',485.00,'',0.00,485.00,'Pendiente'),(20,2,1,1,'2026-08-04 15:49:46','2026-08-04 12:52:00','Entregado',5125.00,'',5125.00,0.00,'Pagado'),(21,1,1,1,'2026-09-19 16:51:40','2026-09-19 10:51:00','Pendiente',469.00,'',0.00,469.00,'Pendiente');
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;

--
-- Table structure for table `permisos`
--

DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permisos` (
  `id_permiso` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_permiso`),
  UNIQUE KEY `uq_permisos_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permisos`
--

/*!40000 ALTER TABLE `permisos` DISABLE KEYS */;
INSERT INTO `permisos` (`id_permiso`, `codigo`, `descripcion`) VALUES (1,'inventory.view','Consultar inventario de sucursales asignadas'),(2,'inventory.view_all','Consultar inventario de todas las sucursales'),(3,'inventory.adjust','Registrar abastecimientos y mermas'),(4,'products.manage','Crear y editar productos y categorias'),(5,'orders.view','Consultar pedidos'),(6,'orders.create','Crear pedidos'),(7,'orders.update','Actualizar pedidos y sus estados'),(8,'sales.view','Consultar ventas y caja'),(9,'sales.create','Registrar ventas directas'),(10,'sales.void','Anular ventas mediante movimientos compensatorios'),(11,'reports.view','Consultar y exportar reportes'),(12,'users.manage','Administrar usuarios autorizados'),(13,'branches.manage','Administrar sucursales'),(14,'clients.manage','Crear, editar y desactivar clientes'),(15,'categories.manage','Crear, editar y desactivar categorias globales'),(16,'cash.adjust','Registrar salidas y ajustes de caja');
/*!40000 ALTER TABLE `permisos` ENABLE KEYS */;

--
-- Table structure for table `personalizacion_grupos`
--

DROP TABLE IF EXISTS `personalizacion_grupos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personalizacion_grupos` (
  `id_grupo` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_grupo`),
  UNIQUE KEY `uq_personalizacion_grupos_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personalizacion_grupos`
--

/*!40000 ALTER TABLE `personalizacion_grupos` DISABLE KEYS */;
INSERT INTO `personalizacion_grupos` (`id_grupo`, `nombre`, `estado`, `fecha_registro`) VALUES (4,'Relleno','Activo','2026-09-19 16:49:03'),(5,'Cobertura','Activo','2026-09-19 16:49:03');
/*!40000 ALTER TABLE `personalizacion_grupos` ENABLE KEYS */;

--
-- Table structure for table `personalizacion_opciones`
--

DROP TABLE IF EXISTS `personalizacion_opciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personalizacion_opciones` (
  `id_opcion` int NOT NULL AUTO_INCREMENT,
  `id_grupo` int NOT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_opcion`),
  UNIQUE KEY `uq_personalizacion_opcion_grupo_nombre` (`id_grupo`,`nombre`),
  CONSTRAINT `fk_personalizacion_opcion_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `personalizacion_grupos` (`id_grupo`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personalizacion_opciones`
--

/*!40000 ALTER TABLE `personalizacion_opciones` DISABLE KEYS */;
INSERT INTO `personalizacion_opciones` (`id_opcion`, `id_grupo`, `nombre`, `estado`, `fecha_registro`) VALUES (7,4,'Dulce de leche','Activo','2026-09-19 16:49:03'),(8,4,'Poleada','Activo','2026-09-19 16:49:03'),(9,4,'Jalea de pina','Activo','2026-09-19 16:49:03'),(10,5,'Topin','Activo','2026-09-19 16:49:03'),(11,5,'Betun','Activo','2026-09-19 16:49:03');
/*!40000 ALTER TABLE `personalizacion_opciones` ENABLE KEYS */;

--
-- Table structure for table `producto_personalizacion_grupos`
--

DROP TABLE IF EXISTS `producto_personalizacion_grupos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_personalizacion_grupos` (
  `id_producto` int NOT NULL,
  `id_grupo` int NOT NULL,
  `minimo_selecciones` tinyint unsigned NOT NULL DEFAULT '0',
  `maximo_selecciones` tinyint unsigned NOT NULL DEFAULT '1',
  `orden` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_producto`,`id_grupo`),
  KEY `fk_producto_personalizacion_grupo_grupo` (`id_grupo`),
  CONSTRAINT `fk_producto_personalizacion_grupo_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `personalizacion_grupos` (`id_grupo`),
  CONSTRAINT `fk_producto_personalizacion_grupo_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `chk_producto_personalizacion_limites` CHECK (((`maximo_selecciones` >= 1) and (`minimo_selecciones` <= `maximo_selecciones`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_personalizacion_grupos`
--

/*!40000 ALTER TABLE `producto_personalizacion_grupos` DISABLE KEYS */;
INSERT INTO `producto_personalizacion_grupos` (`id_producto`, `id_grupo`, `minimo_selecciones`, `maximo_selecciones`, `orden`) VALUES (20,4,1,1,0),(20,5,1,1,1);
/*!40000 ALTER TABLE `producto_personalizacion_grupos` ENABLE KEYS */;

--
-- Table structure for table `producto_personalizacion_opciones`
--

DROP TABLE IF EXISTS `producto_personalizacion_opciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_personalizacion_opciones` (
  `id_producto` int NOT NULL,
  `id_opcion` int NOT NULL,
  `recargo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `predeterminada` tinyint(1) NOT NULL DEFAULT '0',
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `orden` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_producto`,`id_opcion`),
  KEY `idx_producto_opciones_estado` (`id_producto`,`estado`,`orden`),
  KEY `fk_producto_personalizacion_opcion_opcion` (`id_opcion`),
  CONSTRAINT `fk_producto_personalizacion_opcion_opcion` FOREIGN KEY (`id_opcion`) REFERENCES `personalizacion_opciones` (`id_opcion`),
  CONSTRAINT `fk_producto_personalizacion_opcion_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `chk_producto_personalizacion_recargo` CHECK ((`recargo` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_personalizacion_opciones`
--

/*!40000 ALTER TABLE `producto_personalizacion_opciones` DISABLE KEYS */;
INSERT INTO `producto_personalizacion_opciones` (`id_producto`, `id_opcion`, `recargo`, `predeterminada`, `estado`, `orden`) VALUES (20,7,52.00,0,'Activo',0),(20,8,52.00,0,'Activo',1),(20,9,0.00,1,'Activo',2),(20,10,52.00,0,'Activo',0),(20,11,0.00,1,'Activo',1);
/*!40000 ALTER TABLE `producto_personalizacion_opciones` ENABLE KEYS */;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `id_categoria` int DEFAULT NULL,
  `nombre_producto` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `precio_base` decimal(10,2) NOT NULL,
  `imagen_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dias_vida_util` int DEFAULT '0',
  `estado` enum('Activo','Inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id_producto`),
  KEY `id_categoria` (`id_categoria`),
  KEY `idx_productos_estado_nombre` (`estado`,`nombre_producto`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` (`id_producto`, `id_categoria`, `nombre_producto`, `descripcion`, `precio_base`, `imagen_url`, `dias_vida_util`, `estado`) VALUES (1,1,'Pastel Tres Leches','Pastel húmedo tradicional decorado con canela.',25.00,NULL,0,'Activo'),(2,1,'Cheesecake de Fresa','Base de galleta crujiente y crema de queso suave.',30.00,NULL,0,'Activo'),(3,2,'Caja de Macarons x6','Variedad de sabores: pistacho, vainilla y chocolate.',12.50,NULL,0,'Activo'),(4,2,'Muffin de Arándanos','Esponjoso pan dulce con fruta natural.',3.50,NULL,0,'Activo'),(5,3,'Capuccino Grande','Café con leche espumosa y toque de cacao.',4.50,NULL,0,'Activo'),(6,4,'Milhojas','Pan de hojaldre',25.00,'default_product.png',0,'Activo'),(7,4,'Quequitos','Panquequito de chocolate, fresa o vainilla',25.00,'default_product.png',0,'Activo'),(8,4,'prueba caducidad','esto es una prueba',25.00,'default_product.png',1,'Activo'),(9,4,'galletas de chispas','',50.00,'default_product.png',15,'Activo'),(10,4,'Torta borracha','',35.00,'default_product.png',5,'Activo'),(11,3,'Cafe negro',NULL,25.00,'default_product.png',0,'Activo'),(12,4,'prueba1','',25.00,'default_product.png',5,'Activo'),(13,6,'pruebamonse','',1000.00,'default_product.png',1,'Activo'),(14,7,'pastel normal 220','',220.00,'default_product.png',10,'Activo'),(15,7,'Pastel normal 280','',280.00,'default_product.png',10,'Activo'),(16,1,'Pastel normal 360','{\"tipo_producto\":\"pastel\",\"tamano\":\"\",\"cantidad_tortas\":\"2\",\"rellenos\":\"Dulce, poleada y jalea de fresa\",\"coberturas\":\"Chantilly y betun\",\"observaciones\":\"\",\"descripcion_general\":\"\"}',360.00,'default_product.png',5,'Activo'),(17,1,'pastel rico','{\"tipo_producto\":\"pastel\",\"tamano\":\"8\",\"cantidad_tortas\":\"3\",\"rellenos\":\"Fresa, piña, dulce y poleada\",\"coberturas\":\"betun, chantilly y fondant\",\"observaciones\":\"\",\"descripcion_general\":\"\"}',5000.00,'default_product.png',5,'Activo'),(18,4,'Pastel normal redondo 8-10','Producto de panadería / bebidas / otros.',365.00,'default_product.png',5,'Inactivo'),(20,7,'Pastel normal redondo 8-10','{\"tipo_producto\":\"pastel\",\"tamano\":\"\",\"cantidad_tortas\":1,\"descripcion_general\":\"\"}',365.00,'default_product.png',5,'Activo');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;

--
-- Table structure for table `rol_permisos`
--

DROP TABLE IF EXISTS `rol_permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol_permisos` (
  `id_rol` int NOT NULL,
  `id_permiso` int NOT NULL,
  PRIMARY KEY (`id_rol`,`id_permiso`),
  KEY `fk_rol_permisos_permiso` (`id_permiso`),
  CONSTRAINT `fk_rol_permisos_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`),
  CONSTRAINT `fk_rol_permisos_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol_permisos`
--

/*!40000 ALTER TABLE `rol_permisos` DISABLE KEYS */;
INSERT INTO `rol_permisos` (`id_rol`, `id_permiso`) VALUES (1,1),(2,1),(3,1),(4,1),(3,2),(1,3),(2,3),(3,3),(1,4),(3,4),(1,5),(2,5),(3,5),(4,5),(1,6),(2,6),(3,6),(1,7),(2,7),(3,7),(1,8),(2,8),(3,8),(4,8),(1,9),(2,9),(3,9),(3,10),(1,11),(2,11),(3,11),(4,11),(1,12),(3,12),(3,13),(1,14),(3,14),(3,15),(1,16),(3,16);
/*!40000 ALTER TABLE `rol_permisos` ENABLE KEYS */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_rol` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `uq_roles_codigo` (`codigo`),
  UNIQUE KEY `uq_roles_nombre` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id_rol`, `codigo`, `nombre_rol`) VALUES (1,'ADMIN_SUCURSAL','Admin'),(2,'EMPLEADO','Empleado'),(3,'SUPERADMIN','SuperAdmin'),(4,'PROPIETARIO','Propietario');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_migrations` (
  `migration` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` (`migration`, `applied_at`) VALUES ('202609121000_access_control_and_audit.sql','2026-09-13 01:00:17'),('202609121200_customer_permissions.sql','2026-09-13 01:06:50'),('202609121300_category_permissions.sql','2026-09-13 01:09:56'),('202609121400_cash_permissions.sql','2026-09-13 01:20:08'),('202609191000_product_customizations.sql','2026-09-19 16:09:37');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;

--
-- Table structure for table `sucursales`
--

DROP TABLE IF EXISTS `sucursales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sucursales` (
  `id_sucursal` int NOT NULL AUTO_INCREMENT,
  `nombre_sucursal` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('Activa','Inactiva') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activa',
  PRIMARY KEY (`id_sucursal`),
  UNIQUE KEY `uq_sucursales_nombre` (`nombre_sucursal`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sucursales`
--

/*!40000 ALTER TABLE `sucursales` DISABLE KEYS */;
INSERT INTO `sucursales` (`id_sucursal`, `nombre_sucursal`, `direccion`, `telefono`, `fecha_creacion`, `estado`) VALUES (1,'Sucursal No2.','Barrio San José',NULL,'2026-05-16 22:06:59','Activa'),(2,'Sucursal No3.','Carretera internacional CA-4',NULL,'2026-05-16 22:06:59','Activa');
/*!40000 ALTER TABLE `sucursales` ENABLE KEYS */;

--
-- Table structure for table `usuario_permisos`
--

DROP TABLE IF EXISTS `usuario_permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_permisos` (
  `id_usuario` int NOT NULL,
  `id_permiso` int NOT NULL,
  PRIMARY KEY (`id_usuario`,`id_permiso`),
  KEY `fk_usuario_permisos_permiso` (`id_permiso`),
  CONSTRAINT `fk_usuario_permisos_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuario_permisos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_permisos`
--

/*!40000 ALTER TABLE `usuario_permisos` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuario_permisos` ENABLE KEYS */;

--
-- Table structure for table `usuario_sucursales`
--

DROP TABLE IF EXISTS `usuario_sucursales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_sucursales` (
  `id_usuario` int NOT NULL,
  `id_sucursal` int NOT NULL,
  PRIMARY KEY (`id_usuario`,`id_sucursal`),
  KEY `id_sucursal` (`id_sucursal`),
  CONSTRAINT `usuario_sucursales_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `usuario_sucursales_ibfk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_sucursales`
--

/*!40000 ALTER TABLE `usuario_sucursales` DISABLE KEYS */;
INSERT INTO `usuario_sucursales` (`id_usuario`, `id_sucursal`) VALUES (1,1),(2,1),(4,1),(6,1),(1,2),(3,2),(7,2);
/*!40000 ALTER TABLE `usuario_sucursales` ENABLE KEYS */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `id_rol` int NOT NULL,
  `id_sucursal` int DEFAULT NULL,
  `nombre_usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_real` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `estado_usuario` enum('Activo','Inactivo') COLLATE utf8mb4_unicode_ci DEFAULT 'Activo',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `id_rol` (`id_rol`),
  KEY `id_sucursal` (`id_sucursal`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`),
  CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `id_sucursal`, `nombre_usuario`, `password_hash`, `nombre_real`, `email`, `ultimo_login`, `estado_usuario`) VALUES (1,1,NULL,'admin_lolitas','$2y$10$yb1pENql5d3kmdxMvHdZoe7VlHRF1zWKpDDq63jbVAbCuemqGQDOa','Admin General','admin@lolitas.com',NULL,'Activo'),(2,2,1,'empleado111111','$2y$10$889ZH2HmzIiCSaYUha10dO.lnZXWvp2HithOasVZTghsTFJYSNya2','Empleado sucursal No.1',NULL,NULL,'Activo'),(3,2,2,'empleado5','$2y$10$rnBZnpNSl/IBAUlT5yGOtuzpNjQfP1eNa10L.iwZ1Fj9hKRvMP5Si','Empleado sucursal No.2',NULL,NULL,'Activo'),(4,2,1,'empleado3','$2y$10$d8sOETjqUJnTqi1Mb7qAfeUVbTqwd.V9WE5mPkAHtRqn.Y/igJBBe','Jose Rigoberto Acosta',NULL,NULL,'Activo'),(5,3,NULL,'empleado44','$2y$10$bZWxrG1lvltHbejM1dPwUeSRKUEZcH8bXu0.o8HN7o/hyRUrF5NGy','Empleado sucursal No.3',NULL,NULL,'Activo'),(6,2,1,'prueba1','$2y$10$t5Go/qH1Flte6z/6JK1qu.TOBA4hLyR8hmCwByS80OT.V6kaOvjdW','Empleado sucursal No.4',NULL,NULL,'Inactivo'),(7,2,2,'empleadoL','$2y$10$amCyonRYPtHdGX54tuMv9uckw3XuDSczcQKVKFO12mcCxb7d3eAkK','Lesvin Empleado',NULL,NULL,'Activo');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;

--
-- Table structure for table `venta_items`
--

DROP TABLE IF EXISTS `venta_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venta_items` (
  `id_item` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_item`),
  KEY `id_venta` (`id_venta`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `venta_items_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas_directas` (`id_venta`) ON DELETE CASCADE,
  CONSTRAINT `venta_items_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_items`
--

/*!40000 ALTER TABLE `venta_items` DISABLE KEYS */;
INSERT INTO `venta_items` (`id_item`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario`, `subtotal`) VALUES (1,1,6,5,25.00,125.00),(2,2,6,5,25.00,125.00),(3,3,5,2,4.50,9.00),(4,4,8,12,25.00,300.00),(5,5,5,1,4.50,4.50),(6,5,2,1,30.00,30.00),(7,6,5,1,4.50,4.50),(8,6,6,1,25.00,25.00),(9,6,7,15,25.00,375.00),(10,7,5,1,4.50,4.50),(11,8,2,1,30.00,30.00),(12,9,10,20,35.00,700.00),(13,10,13,5,1000.00,5000.00),(14,10,4,1,3.50,3.50),(15,11,11,1,25.00,25.00),(16,11,5,1,4.50,4.50),(17,12,2,1,30.00,30.00),(18,12,1,1,25.00,25.00),(19,13,1,10,25.00,250.00),(20,14,11,1,25.00,25.00),(21,15,2,1,30.00,30.00),(22,16,4,1,3.50,3.50),(23,17,6,6,25.00,150.00),(24,17,5,2,4.50,9.00),(25,18,2,1,30.00,30.00),(26,19,11,1,25.00,25.00),(27,19,5,1,4.50,4.50),(28,20,9,1,50.00,50.00),(29,20,4,1,3.50,3.50),(30,21,11,1,25.00,25.00),(31,22,2,1,30.00,30.00),(32,23,11,1,25.00,25.00),(33,24,7,1,25.00,25.00),(34,25,1,2,25.00,50.00),(35,25,6,2,25.00,50.00),(36,26,6,6,25.00,150.00),(37,27,11,3,25.00,75.00),(38,28,20,2,365.00,730.00);
/*!40000 ALTER TABLE `venta_items` ENABLE KEYS */;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_sucursal` int NOT NULL,
  `cantidad` int NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha_venta` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_venta`),
  KEY `fk_venta_producto` (`id_producto`),
  KEY `fk_venta_usuario` (`id_usuario`),
  KEY `fk_venta_sucursal` (`id_sucursal`),
  CONSTRAINT `fk_venta_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `fk_venta_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`),
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;

--
-- Table structure for table `ventas_directas`
--

DROP TABLE IF EXISTS `ventas_directas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas_directas` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_sucursal` int NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Transferencia','Tarjeta','Otro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Efectivo',
  `fecha_venta` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_venta`),
  KEY `id_usuario` (`id_usuario`),
  KEY `idx_ventas_sucursal_fecha` (`id_sucursal`,`fecha_venta`),
  CONSTRAINT `ventas_directas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `ventas_directas_ibfk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas_directas`
--

/*!40000 ALTER TABLE `ventas_directas` DISABLE KEYS */;
INSERT INTO `ventas_directas` (`id_venta`, `id_usuario`, `id_sucursal`, `total`, `metodo_pago`, `fecha_venta`) VALUES (1,1,1,125.00,'Efectivo','2026-05-16 22:21:28'),(2,3,2,125.00,'Efectivo','2026-05-20 02:35:51'),(3,1,1,9.00,'Efectivo','2026-05-23 18:20:41'),(4,1,1,300.00,'Efectivo','2026-05-23 18:21:08'),(5,1,1,34.50,'Efectivo','2026-05-26 00:37:28'),(6,1,1,404.50,'Efectivo','2026-05-26 00:38:25'),(7,1,1,4.50,'Efectivo','2026-05-26 01:00:48'),(8,1,2,30.00,'Efectivo','2026-05-26 01:05:48'),(9,1,2,700.00,'Efectivo','2026-05-26 01:06:35'),(10,1,2,5003.50,'Efectivo','2026-05-26 03:36:19'),(11,1,1,29.50,'Efectivo','2026-05-26 03:53:50'),(12,1,2,55.00,'Efectivo','2026-05-26 03:54:51'),(13,1,1,250.00,'Efectivo','2026-06-13 16:41:00'),(14,1,1,25.00,'Efectivo','2026-06-13 16:46:49'),(15,3,2,30.00,'Efectivo','2026-06-13 16:47:24'),(16,1,2,3.50,'Efectivo','2026-06-13 17:11:00'),(17,2,1,159.00,'Efectivo','2026-06-17 01:42:08'),(18,1,2,30.00,'Efectivo','2026-06-25 13:35:15'),(19,1,1,29.50,'Efectivo','2026-07-13 20:46:57'),(20,1,2,53.50,'Efectivo','2026-07-13 20:47:05'),(21,1,1,25.00,'Efectivo','2026-07-14 16:27:30'),(22,1,2,30.00,'Efectivo','2026-07-14 16:38:52'),(23,1,1,25.00,'Efectivo','2026-08-04 15:19:30'),(24,1,1,25.00,'Efectivo','2026-08-04 15:27:04'),(25,1,1,100.00,'Efectivo','2026-08-04 15:51:20'),(26,1,1,150.00,'Efectivo','2026-08-04 15:51:39'),(27,1,1,75.00,'Efectivo','2026-09-19 17:03:14'),(28,1,1,730.00,'Efectivo','2026-09-19 17:08:02');
/*!40000 ALTER TABLE `ventas_directas` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-19 12:24:29
