<?php
class Usuario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        // Consulta exacta con tus nombres de columna
        $query = "SELECT u.*, r.nombre_rol 
                  FROM usuarios u
                  INNER JOIN roles r ON u.id_rol = r.id_rol
                  WHERE u.nombre_usuario = :user AND u.estado_usuario = 'Activo' LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificación usando la columna password_hash de tu DB
        if ($user && password_verify($password, $user['password_hash'])) {
            $user['sucursales_asignadas'] = [];
            // Si es Admin (Rol 1), buscamos sus sucursales en la tabla intermedia
            if ($user['id_rol'] == 1) {
                $sqlSuc = "SELECT id_sucursal FROM usuario_sucursales WHERE id_usuario = :id";
                $stmtSuc = $this->conn->prepare($sqlSuc);
                $stmtSuc->execute([':id' => $user['id_usuario']]);
                $user['sucursales_asignadas'] = $stmtSuc->fetchAll(PDO::FETCH_COLUMN);
            }
            return $user;
        }
        return false;
    }

    public function obtenerTodos() {
        $query = "SELECT u.id_usuario, u.nombre_usuario, u.estado_usuario, u.id_rol,
                         r.nombre_rol, s.nombre_sucursal,
                         (SELECT GROUP_CONCAT(s2.nombre_sucursal SEPARATOR ', ') 
                          FROM usuario_sucursales us 
                          JOIN sucursales s2 ON us.id_sucursal = s2.id_sucursal 
                          WHERE us.id_usuario = u.id_usuario) as sucursales_admin
                  FROM usuarios u
                  INNER JOIN roles r ON u.id_rol = r.id_rol
                  LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
                  ORDER BY u.id_usuario DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
