<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/core/Database.php';

$db = (new Database())->getConnection();
$roles = $db->query('SELECT id_rol, nombre_rol FROM roles ORDER BY id_rol')->fetchAll(PDO::FETCH_ASSOC);
echo 'roles: ' . json_encode($roles, JSON_UNESCAPED_UNICODE) . "\n";
$branchCount = (int) $db->query("SELECT COUNT(*) FROM sucursales WHERE estado = 'Activa'")->fetchColumn();
$unassignedUsers = (int) $db->query(
    "SELECT COUNT(*) FROM usuarios u
     WHERE u.id_rol <> 3 AND u.estado_usuario = 'Activo'
       AND NOT EXISTS (SELECT 1 FROM usuario_sucursales us WHERE us.id_usuario = u.id_usuario)"
)->fetchColumn();
echo 'sucursales_activas: ' . $branchCount . "\n";
echo 'usuarios_activos_sin_sucursal: ' . $unassignedUsers . "\n";
$checks = [
    'categorias_duplicadas' => 'SELECT COUNT(*) FROM (SELECT nombre_categoria FROM categorias GROUP BY nombre_categoria HAVING COUNT(*) > 1) duplicated',
    'sucursales_duplicadas' => 'SELECT COUNT(*) FROM (SELECT nombre_sucursal FROM sucursales GROUP BY nombre_sucursal HAVING COUNT(*) > 1) duplicated',
    'mermas_sucursal_huerfana' => 'SELECT COUNT(*) FROM mermas_caja m LEFT JOIN sucursales s ON s.id_sucursal = m.id_sucursal WHERE s.id_sucursal IS NULL',
    'mermas_usuario_huerfano' => 'SELECT COUNT(*) FROM mermas_caja m LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario WHERE u.id_usuario IS NULL',
];

$failed = false;
foreach ($checks as $name => $sql) {
    $count = (int) $db->query($sql)->fetchColumn();
    echo $name . ': ' . $count . "\n";
    $failed = $failed || $count > 0;
}

exit($failed ? 1 : 0);
