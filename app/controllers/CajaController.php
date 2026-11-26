<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';

use App\Http\Response;
use App\Http\Validator;
use App\Reports\CashReportPdf;
use App\Security\Auth;
use App\Security\Csrf;
use App\Services\CashClosingService;
use App\Services\CashDrawerSession;

final class CajaController
{
    private CashClosingService $closing;

    public function __construct(private PDO $db)
    {
        $this->closing = new CashClosingService($db);
    }

    public function page(array $query): array
    {
        $branches = array_filter(
            $this->db->query('SELECT id_sucursal, nombre_sucursal, estado FROM sucursales ORDER BY nombre_sucursal')
                ->fetchAll(PDO::FETCH_ASSOC),
            static fn (array $branch): bool => Auth::canAccessBranch((int) $branch['id_sucursal'], 'reports.view')
        );
        $branches = array_column($branches, null, 'id_sucursal');
        $preferred = (int) ($_SESSION['id_sucursal'] ?? 0);
        $branchId = isset($query['sucursal'])
            ? Validator::positiveInt($query['sucursal'], 'sucursal')
            : (isset($branches[$preferred]) ? $preferred : array_key_first($branches));
        $branch = $branches[$branchId] ?? null;
        if (isset($query['sucursal']) && !$branch) throw new InvalidArgumentException('La sucursal no está disponible.');

        $latest = $branch ? $this->closing->latest($branchId) : null;
        $requested = isset($query['id_caja']) ? Validator::positiveInt($query['id_caja'], 'jornada de caja') : null;
        $id = $requested ?? ($latest['id_caja'] ?? null);
        $session = $id !== null && $branch ? $this->closing->find($branchId, (int) $id) : null;
        if ($requested !== null && !$session) throw new InvalidArgumentException('La jornada no está disponible.');

        $flash = $_SESSION['cash_flash'] ?? null;
        unset($_SESSION['cash_flash']);
        $now = CashDrawerSession::now();
        return [
            'branches' => $branches,
            'branch' => $branch,
            'branchId' => $branchId,
            'session' => $session,
            'history' => $branch ? $this->closing->history($branchId) : [],
            'flash' => $flash,
            'canClose' => $branch && Auth::canAccessBranch($branchId, 'cash.close'),
            'canOpen' => $branch && $branch['estado'] === 'Activa' && $requested === null
                && (!$latest || ($latest['estado'] === 'Cerrada' && $latest['fecha_operativa'] < $now->format('Y-m-d'))),
            'canCount' => $session && ($session['fecha_operativa'] < $now->format('Y-m-d')
                || $now->format('H:i') >= CashDrawerSession::CLOSING_TIME),
            'canReceive' => $branch && $session && $session['estado'] === 'Cerrada'
                && $session['recibido_por'] === null && (int) $session['cerrado_por'] !== (int) $_SESSION['id_usuario']
                && Auth::canAccessBranch($branchId, 'cash.receive'),
        ];
    }

    public function change(): void
    {
        Csrf::validateRequest();
        $action = Validator::enum($_POST['operacion'] ?? '', ['abrir', 'contar', 'cancelar', 'cerrar', 'recibir'], 'operación');
        $branch = Validator::positiveInt($_POST['sucursal'] ?? null, 'sucursal');
        Auth::requirePermission($action === 'recibir' ? 'cash.receive' : 'cash.close', $branch);
        $id = $action === 'abrir' ? null : Validator::positiveInt($_POST['id_caja'] ?? null, 'jornada de caja');
        try {
            $message = $this->closing->change($action, $branch, (int) $_SESSION['id_usuario'], $_POST);
            $_SESSION['cash_flash'] = ['type' => 'success', 'message' => $message];
        } catch (InvalidArgumentException $error) {
            $_SESSION['cash_flash'] = [
                'type' => 'danger', 'message' => $error->getMessage(), 'id' => $id,
                'input' => array_intersect_key($_POST, array_flip(['contado', 'recibido', 'nota', 'sobres'])),
            ];
        }

        $location = 'index.php?view=caja&sucursal=' . $branch;
        if ($action === 'recibir') $location .= '&id_caja=' . $id;
        header('Location: ' . $location, true, 303);
        exit;
    }

    public function pdf(): void
    {
        $branch = Validator::positiveInt($_GET['sucursal'] ?? null, 'sucursal');
        Auth::requirePermission('reports.view', $branch);
        $id = Validator::positiveInt($_GET['id_caja'] ?? null, 'jornada de caja');
        $session = $this->closing->find($branch, $id);
        if (!$session || !$session['informe']) {
            Response::json(['status' => 'error', 'message' => 'El PDF está disponible después de iniciar el conteo.'], 404);
        }
        CashReportPdf::stream($session['informe'], $session);
    }
}

if (($_GET['resource'] ?? '') === 'caja') {
    Auth::requireLogin();
    $controller = new CajaController((new Database())->getConnection());
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    if ($action === 'pdf' && $method === 'GET') $controller->pdf();
    if ($action === 'change' && $method === 'POST') $controller->change();
    Response::json(['status' => 'error', 'message' => 'Acción de caja no disponible.'], 404);
}
