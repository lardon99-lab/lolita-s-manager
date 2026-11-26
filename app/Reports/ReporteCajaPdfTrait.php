<?php

use App\Http\Validator;
use App\Reports\CashReportPdf;
use App\Reports\DailyCashReport;
use App\Security\Auth;
use App\Services\CashDrawerSession;

trait ReporteCajaPdfTrait
{
    public function descargarReportePDF(array $filtros = []): void
    {
        $branch = !empty($filtros['sucursal']) ? Validator::positiveInt($filtros['sucursal'], 'sucursal') : null;
        Auth::requirePermission('reports.view', $branch);
        $now = CashDrawerSession::now();
        $branches = $branch === null ? Auth::allowedBranches('reports.view') : [$branch];
        $report = (new DailyCashReport($this->db))->data($branches, $now->format('Y-m-d'));
        $report['capturado_en'] = $now->format('Y-m-d H:i:s');
        CashReportPdf::stream($report);
    }
}
