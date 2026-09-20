<?php
declare(strict_types=1);

use App\Services\ClientService;
use PHPUnit\Framework\TestCase;

final class ClientServiceTest extends TestCase
{
    private PDO $db;
    private ClientService $service;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec("CREATE TABLE clientes (id_cliente INTEGER PRIMARY KEY AUTOINCREMENT, nombre_completo TEXT, telefono TEXT, email TEXT, estado TEXT DEFAULT 'Activo')");
        $this->db->exec("INSERT INTO clientes (nombre_completo, telefono, estado) VALUES ('Ana Martinez', '9999-1111', 'Activo'), ('Cliente inactivo', '9999-2222', 'Inactivo')");
        $this->service = new ClientService($this->db);
    }

    public function testSearchesActiveClientsByPhone(): void
    {
        $result = $this->service->search('1111');
        self::assertCount(1, $result);
        self::assertSame('Ana Martinez', $result[0]['nombre_completo']);
    }

    public function testCreatesAValidatedClient(): void
    {
        $client = $this->service->create([
            'nombre' => 'Carlos Lopez',
            'telefono' => '8888-0000',
            'email' => 'carlos@example.com',
        ]);
        self::assertGreaterThan(0, $client['id_cliente']);
        self::assertSame('Carlos Lopez', $client['nombre_completo']);
    }
}
