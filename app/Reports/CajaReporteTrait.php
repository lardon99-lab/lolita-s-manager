<?php

use App\Security\Auth;

trait CajaReporteTrait
{
    public function obtenerHistorialVentas($filtros = []) {
        Auth::requirePermission('reports.view');
        $params = [];
        
        // 0. Identificar quién está mirando (Seguridad por Rol)
        $id_rol = (int)($_SESSION['id_rol'] ?? 0);
        $sucursal_usuario = (int)($_SESSION['id_sucursal'] ?? 0);

        // 1. Condiciones base
        $condicionPedidos = " WHERE p.estado = 'Entregado'";
        $condicionVentas = " WHERE 1=1";

        // --- LÓGICA DE FILTRADO POR ROL ---
        if (!empty($filtros['sucursal'])) {
            if (!Auth::canAccessBranch((int) $filtros['sucursal'], 'reports.view')) return [];
            $condicionPedidos .= " AND p.id_sucursal = :emp_s1";
            $condicionVentas .= " AND v.id_sucursal = :emp_s2";
            $params[':emp_s1'] = $params[':emp_s2'] = (int) $filtros['sucursal'];
        } elseif ($id_rol === 2) {
            // Si es EMPLEADO: Filtro obligatorio por su sucursal asignada
            $condicionPedidos .= " AND p.id_sucursal = :emp_s1";
            $condicionVentas .= " AND v.id_sucursal = :emp_s2";
            $params[':emp_s1'] = $params[':emp_s2'] = $sucursal_usuario;
        } else {
            if (!empty($filtros['sucursal'])) {
                if (!Auth::canAccessBranch((int) $filtros['sucursal'], 'reports.view')) return [];
                $condicionPedidos .= " AND p.id_sucursal = :s1";
                $condicionVentas .= " AND v.id_sucursal = :s2";
                $params[':s1'] = $params[':s2'] = $filtros['sucursal'];
            } elseif ($id_rol !== Auth::SUPERUSER) {
                $allowed = Auth::allowedBranches('reports.view') ?? [];
                if ($allowed === []) return [];
                $pedidoHolders = [];
                $ventaHolders = [];
                foreach ($allowed as $index => $branchId) {
                    $pedidoKey = ':history_p_' . $index;
                    $ventaKey = ':history_v_' . $index;
                    $pedidoHolders[] = $pedidoKey;
                    $ventaHolders[] = $ventaKey;
                    $params[$pedidoKey] = $branchId;
                    $params[$ventaKey] = $branchId;
                }
                $condicionPedidos .= ' AND p.id_sucursal IN (' . implode(',', $pedidoHolders) . ')';
                $condicionVentas .= ' AND v.id_sucursal IN (' . implode(',', $ventaHolders) . ')';
            }
        }

        // Filtros de fecha (Comunes para todos)
        if (!empty($filtros['desde']) && !empty($filtros['hasta'])) {
            $condicionPedidos .= " AND DATE(p.fecha_registro) BETWEEN :d1 AND :h1";
            $condicionVentas .= " AND DATE(v.fecha_venta) BETWEEN :d2 AND :h2";
            $params[':d1'] = $params[':d2'] = $filtros['desde'];
            $params[':h1'] = $params[':h2'] = $filtros['hasta'];
        }

        // 2. PARTE A: Pedidos Especiales
        $sqlPedidos = "SELECT p.id_pedido as id_pedido, 
                            p.fecha_registro as fecha_registro, 
                            c.nombre_completo as cliente, 
                            s.nombre_sucursal as nombre_sucursal, 
                            p.total_pedido as total_pedido, 
                            'Pedido Especial' as tipo,
                            GROUP_CONCAT(CONCAT('• ', pr.nombre_producto, ' (', det.cantidad, ')', 
                                IF(det.detalles_personalizacion IS NOT NULL AND det.detalles_personalizacion <> '', 
                                CONCAT(' [', det.detalles_personalizacion, ']'), '')
                            ) SEPARATOR '<br>') as productos
                        FROM pedidos p
                        INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                        INNER JOIN sucursales s ON p.id_sucursal = s.id_sucursal
                        INNER JOIN pedido_detalles det ON p.id_pedido = det.id_pedido
                        INNER JOIN productos pr ON det.id_producto = pr.id_producto
                        $condicionPedidos
                        GROUP BY p.id_pedido";

        // 3. PARTE B: Ventas Directas
        $sqlVentas = "SELECT v.id_venta as id_pedido, 
                            v.fecha_venta as fecha_registro, 
                            'Público General' as cliente, 
                            s.nombre_sucursal as nombre_sucursal, 
                            v.total as total_pedido, 
                            'Venta Directa' as tipo,
                            GROUP_CONCAT(CONCAT('• ', pr.nombre_producto, ' (', vi.cantidad, ')') SEPARATOR '<br>') as productos
                        FROM ventas_directas v
                        INNER JOIN sucursales s ON v.id_sucursal = s.id_sucursal
                        INNER JOIN venta_items vi ON v.id_venta = vi.id_venta
                        INNER JOIN productos pr ON vi.id_producto = pr.id_producto
                        $condicionVentas
                        GROUP BY v.id_venta";

        // 4. Unión y Filtro Final
        $sqlFinal = "($sqlPedidos) UNION ALL ($sqlVentas)";
        
        $outerConditions = [];
        if (!empty($filtros['tipo'])) {
            $outerConditions[] = 'tipo = :tipo_filtro';
            $params[':tipo_filtro'] = ($filtros['tipo'] == 'Pedido') ? 'Pedido Especial' : 'Venta Directa';
        }
        if (!empty($filtros['busqueda'])) {
            $search = \App\Http\Validator::text($filtros['busqueda'], 'busqueda', 100);
            $outerConditions[] = '(cliente LIKE :search_client OR CAST(id_pedido AS CHAR) LIKE :search_id)';
            $params[':search_client'] = '%' . $search . '%';
            $params[':search_id'] = '%' . $search . '%';
        }
        if ($outerConditions !== []) $sqlFinal = "SELECT * FROM ($sqlFinal) as historial WHERE " . implode(' AND ', $outerConditions);

        $sqlFinal .= " ORDER BY fecha_registro DESC";

        try {
            $stmt = $this->db->prepare($sqlFinal);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            \App\Support\Logger::error($e);
            return [];
        }
    }

    public function guardarMerma() {
        Auth::requirePermission('cash.adjust');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $monto = \App\Http\Validator::money($_POST['monto_merma'] ?? null, 'monto', 1000000);
                if ($monto <= 0) throw new InvalidArgumentException('El monto debe ser mayor a cero.');
                $motivo = \App\Http\Validator::text($_POST['motivo_merma'] ?? '', 'motivo', 100);
                $descripcion = \App\Http\Validator::text($_POST['descripcion_merma'] ?? '', 'descripcion', 500, false);
                
                // MANEJO SEGURO: Busca en POST primero (Admin), si no, en SESSION (Empleado)
                $id_sucursal = \App\Http\Validator::positiveInt($_POST['id_sucursal'] ?? ($_SESSION['id_sucursal'] ?? null), 'sucursal');
                Auth::requirePermission('cash.adjust', $id_sucursal);
                
                $id_usuario = $_SESSION['id_usuario'] ?? 0;

                // Validación extra por seguridad
                if (empty($id_sucursal)) {
                    die("Error: No se detectó a qué sucursal pertenece esta merma. Si eres administrador, asegúrate de seleccionar una sucursal en el formulario.");
                }

                $query = "INSERT INTO mermas_caja (monto, motivo, descripcion, id_sucursal, id_usuario) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$monto, $motivo, $descripcion, $id_sucursal, $id_usuario]);

                // Redirigimos de vuelta
                header("Location: index.php?view=ventas-historial&msg=merma_ok", true, 303);
                exit();
            } catch (Exception $e) {
                \App\Support\Logger::error($e);
                die("No fue posible registrar la merma.");
            }
        }
    }

    public function obtenerMermas($filtros = []) {
        Auth::requirePermission('reports.view');
        $id_rol = (int)($_SESSION['id_rol'] ?? 0);
        $sucursal_usuario = (int)($_SESSION['id_sucursal'] ?? 0);
        
        $params = [];
        $condiciones = "WHERE 1=1";

        // Filtro de sucursal igual que en ventas
        if ($id_rol === 2) {
            $condiciones .= " AND id_sucursal = :s1";
            $params[':s1'] = $sucursal_usuario;
        } else {
            if (!empty($filtros['sucursal'])) {
                if (!Auth::canAccessBranch((int) $filtros['sucursal'], 'reports.view')) return [];
                $condiciones .= " AND id_sucursal = :s1";
                $params[':s1'] = $filtros['sucursal'];
            } elseif ($id_rol !== Auth::SUPERUSER) {
                $allowed = Auth::allowedBranches('reports.view') ?? [];
                if ($allowed === []) return [];
                $holders = [];
                foreach ($allowed as $index => $branchId) {
                    $key = ':waste_branch_' . $index;
                    $holders[] = $key;
                    $params[$key] = $branchId;
                }
                $condiciones .= ' AND id_sucursal IN (' . implode(',', $holders) . ')';
            }
        }

        // Filtro de fechas
        if (!empty($filtros['desde']) && !empty($filtros['hasta'])) {
            $condiciones .= " AND DATE(fecha_registro) BETWEEN :d1 AND :h1";
            $params[':d1'] = $filtros['desde'];
            $params[':h1'] = $filtros['hasta'];
        }

        try {
            $query = "SELECT * FROM mermas_caja $condiciones ORDER BY fecha_registro DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            \App\Support\Logger::error($e);
            return [];
        }
    }

    public function obtenerMermasInventario($filtros = []) {
        Auth::requirePermission('reports.view');
        $id_rol = (int)($_SESSION['id_rol'] ?? 0);
        $sucursal_usuario = (int)($_SESSION['id_sucursal'] ?? 0);

        $params = [];
        $condiciones = "WHERE 1=1";

        if ($id_rol === 2) {
            $condiciones .= " AND i.id_sucursal = :s1";
            $params[':s1'] = $sucursal_usuario;
        } else {
            if (!empty($filtros['sucursal'])) {
                if (!Auth::canAccessBranch((int) $filtros['sucursal'], 'reports.view')) return [];
                $condiciones .= " AND i.id_sucursal = :s1";
                $params[':s1'] = $filtros['sucursal'];
            } elseif ($id_rol !== Auth::SUPERUSER) {
                $allowed = Auth::allowedBranches('reports.view') ?? [];
                if ($allowed === []) return [];
                $holders = [];
                foreach ($allowed as $index => $branchId) {
                    $key = ':inventory_waste_branch_' . $index;
                    $holders[] = $key;
                    $params[$key] = $branchId;
                }
                $condiciones .= ' AND i.id_sucursal IN (' . implode(',', $holders) . ')';
            }
        }

        try {
            $query = "SELECT m.cantidad, m.motivo, p.nombre_producto
                      FROM mermas m
                      JOIN inventario i ON m.id_inventario = i.id_inventario
                      JOIN productos p ON i.id_producto = p.id_producto
                      $condiciones
                      ORDER BY m.id_merma DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            \App\Support\Logger::error($e);
            return [];
        }
    }

}
