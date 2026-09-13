<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$view = $argv[1] ?? 'dashboard';
$roleId = isset($argv[2]) ? (int) $argv[2] : 3;
$branches = isset($argv[3]) ? array_values(array_filter(array_map('intval', explode(',', $argv[3])))) : [];
require_once dirname(__DIR__) . '/app/core/Database.php';

$db = (new Database())->getConnection();
$user = $db->query("SELECT id_usuario, nombre_usuario FROM usuarios WHERE estado_usuario = 'Activo' ORDER BY id_usuario LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$user) throw new RuntimeException('No existe un usuario activo para ejecutar el smoke test.');
$permissionStmt = $db->prepare('SELECT p.codigo FROM rol_permisos rp JOIN permisos p ON p.id_permiso = rp.id_permiso WHERE rp.id_rol = ? ORDER BY p.codigo');
$permissionStmt->execute([$roleId]);
$permissions = $permissionStmt->fetchAll(PDO::FETCH_COLUMN);
if (!empty($argv[4])) $permissions[] = 'inventory.view_all';
$_SESSION = [
    'id_usuario' => (int) $user['id_usuario'],
    'username' => (string) $user['nombre_usuario'],
    'id_rol' => $roleId,
    'id_sucursal' => $roleId === 2 ? ($branches[0] ?? null) : null,
    'sucursales' => $branches,
    'permissions' => $permissions,
    'ultimo_acceso' => time(),
];
$_GET = ['view' => $view];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = '127.0.0.1';
chdir(dirname(__DIR__) . '/public');

ob_start();
require 'index.php';
$html = (string) ob_get_clean();
if (!str_contains($html, '<!DOCTYPE html>') || !str_contains($html, 'main-content')) {
    throw new RuntimeException("La vista {$view} no genero una pagina completa.");
}
echo $view . ': ' . strlen($html) . " bytes\n";
