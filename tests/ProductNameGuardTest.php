<?php
declare(strict_types=1);

use App\Services\ProductNameGuard;
use PHPUnit\Framework\TestCase;

final class ProductNameGuardTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE productos (id_producto INTEGER PRIMARY KEY, nombre_producto TEXT NOT NULL, estado TEXT NOT NULL)');
        $this->db->exec("INSERT INTO productos VALUES (1, 'Pastel Tres Leches', 'Activo'), (2, 'Milhojas', 'Inactivo')");
    }

    public function testExistingProductNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ya existe un producto con ese nombre.');
        (new ProductNameGuard($this->db))->assertAvailable('PASTEL TRES LECHES');
    }

    public function testCurrentProductCanKeepItsNameDuringEdition(): void
    {
        (new ProductNameGuard($this->db))->assertAvailable('Pastel Tres Leches', 1);
        self::assertTrue(true);
    }

    public function testInactiveDuplicateSuggestsReactivation(): void
    {
        $this->expectExceptionMessage('reactivalo');
        (new ProductNameGuard($this->db))->assertAvailable('Milhojas');
    }
}
