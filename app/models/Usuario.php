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

    // Función para validar login
    public function login($user, $pass) {
        // Buscamos al usuario y traemos su rol y sucursal
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
            
            // Verificamos la contraseña cifrada
            if(password_verify($pass, $row['password_hash'])) {
                return $row; // Retornamos los datos del usuario para la sesión
            }
        }
        return false;
    }
}
?>