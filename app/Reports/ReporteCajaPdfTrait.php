<?php

use App\Http\Validator;
use App\Reports\CashReportPdf;
use App\Reports\DailyCashReport;
use App\Security\Auth;
use App\Services\CashClosingService;
use App\Services\CashDrawerSession;

trait ReporteCajaPdfTrait
{
    public function descargarReportePDF(array $filtros = []): void
    {
        $branch = !empty($filtros['sucursal']) ? Validator::positiveInt($filtros['sucursal'], 'sucursal') : null;
        Auth::requirePermission('reports.view', $branch);
        $now = CashDrawerSession::now();
        $branches = $branch === null ? Auth::allowedBranches('reports.view') : [$branch];
        $day = $now->format('Y-m-d');
        $singleBranch = count($branches ?? []) === 1 ? reset($branches) : null;
        if ($singleBranch !== null) {
            $closing = new CashClosingService($this->db);
            $latest = $closing->latest($singleBranch);
            if (($latest['fecha_operativa'] ?? null) === $day && !empty($latest['informe'])) {
                $session = $closing->find($singleBranch, (int) $latest['id_caja']);
                if (!empty($session['informe'])) CashReportPdf::stream($session['informe'], $session);
            }
        }
        $report = (new DailyCashReport($this->db))->data($branches, $day);
        $report['capturado_en'] = $now->format('Y-m-d H:i:s');
        CashReportPdf::stream($report);
    }
}
