<?php
class Database {
    private $host = "localhost";
    private $db_name = "lolitas_db"; // Nombre actualizado
    private $username = "root";      // Cambia según tu config
    private $password = "ard@1931$";          // Cambia según tu config
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            // Configuramos para que lance excepciones en caso de error
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Definimos el juego de caracteres a UTF8
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>