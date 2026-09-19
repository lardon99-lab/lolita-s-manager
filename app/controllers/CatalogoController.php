<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

use App\Http\Response;
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\AuditService;
use App\Services\CatalogService;
use App\Services\ProductCustomizationService;

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
            'guardar_personalizacion' => (new ProductCustomizationService($this->db))->save($_POST),
            default => null,
        };
        if ($id === null) Response::json(['status' => 'error', 'message' => 'Accion no encontrada.'], 404);
        $entity = $action === 'guardar_personalizacion' || str_contains($action, 'producto') ? 'productos' : 'categorias';
        $audit->record('catalog.' . $action, $entity, $id);
        Response::json(['status' => 'success', 'message' => 'Catalogo actualizado.']);
    }

    public function customization(): void
    {
        $id = \App\Http\Validator::positiveInt($_GET['id_producto'] ?? null, 'producto');
        $configuration = (new ProductCustomizationService($this->db))->configuration($id);
        Response::json(['status' => 'success', 'configuracion' => $configuration]);
    }
}

if (isset($_GET['action'])) {
    Auth::requireLogin();
    $action = (string) $_GET['action'];
    $controller = new CatalogoController();
    if ($action === 'configuracion_producto') {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
        $controller->customization();
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::json(['status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    Csrf::validateRequest();
    $controller->ejecutar($action);
}
