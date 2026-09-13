<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

use App\Http\Response;
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\AuditService;
use App\Services\CatalogService;

final class CatalogoController
{
    private PDO $db;
    private CatalogService $service;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->service = new CatalogService($this->db);
    }

    public function listar(): array
    {
        return $this->service->data();
    }

    public function ejecutar(string $action): void
    {
        $audit = new AuditService($this->db);
        $id = match ($action) {
            'actualizar_producto' => $this->service->updateProduct($_POST),
            'estado_producto' => $this->service->setProductState($_POST),
            'guardar_categoria' => $this->service->saveCategory($_POST),
            'estado_categoria' => $this->service->setCategoryState($_POST),
            default => null,
        };
        if ($id === null) Response::json(['status' => 'error', 'message' => 'Accion no encontrada.'], 404);
        $audit->record('catalog.' . $action, str_contains($action, 'producto') ? 'productos' : 'categorias', $id);
        Response::json(['status' => 'success', 'message' => 'Catalogo actualizado.']);
    }
}

if (isset($_GET['action'])) {
    Auth::requireLogin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    Csrf::validateRequest();
    (new CatalogoController())->ejecutar((string) $_GET['action']);
}
