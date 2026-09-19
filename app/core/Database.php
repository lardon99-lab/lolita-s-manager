<?php
declare(strict_types=1);

use App\Support\Env;

require_once __DIR__ . '/../bootstrap.php';

class Database
{
    private ?PDO $conn = null;

    public function getConnection(): PDO
    {
        if ($this->conn instanceof PDO) return $this->conn;
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('La extension pdo_mysql no esta habilitada.');
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $database = Env::get('DB_DATABASE', 'lolitas_db');
        $username = Env::get('DB_USERNAME', 'root');
        $password = Env::get('DB_PASSWORD', '');

        $this->conn = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]
        );

        // Align CURRENT_TIMESTAMP and TIMESTAMP retrieval with APP_TIMEZONE.
        $timezone = new DateTimeZone(date_default_timezone_get());
        $offset = (new DateTimeImmutable('now', $timezone))->format('P');
        $this->conn->exec('SET time_zone = ' . $this->conn->quote($offset));

        return $this->conn;
    }
}
