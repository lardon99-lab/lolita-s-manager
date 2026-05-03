<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    // Propiedades del objeto
    public $id_usuario;
    public $id_rol;
    public $nombre_usuario;
    public $password_hash;
    public $nombre_real;
    public $id_sucursal;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($user, $pass) {
        $query = "SELECT u.*, r.nombre_rol 
                  FROM " . $this->table_name . " u
                  INNER JOIN roles r ON u.id_rol = r.id_rol
                  WHERE u.nombre_usuario = :user AND u.estado_usuario = 'Activo' 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user', $user);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($pass, $row['password_hash'])) {
                return $row; // Retornamos los datos del usuario para la sesión
            }
        }
        return false;
    }
    public function crear($datos) {
        $sql = "INSERT INTO usuarios (nombre_usuario, password, rol, id_sucursal, estado) 
                VALUES (:user, :pass, :rol, :id_s, 'Activo')";
        
        $stmt = $this->conn->prepare($sql);
        
        $password_segura = password_hash($datos['password'], PASSWORD_DEFAULT);
        
        return $stmt->execute([
            ':user' => $datos['nombre_usuario'],
            ':pass' => $password_segura,
            ':rol'  => $datos['rol'],
            ':id_s' => $datos['id_sucursal']
        ]);
    }
}
?>