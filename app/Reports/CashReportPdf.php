<?php
declare(strict_types=1);

namespace App\Reports;

use Dompdf\Dompdf;

final class CashReportPdf
{
    public static function stream(array $report, ?array $session = null): void
    {
        ob_start();
        require __DIR__ . '/../../views/ventas/caja-pdf.php';
        $html = ob_get_clean();

        $pdf = new Dompdf();
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();
        $name = ($session === null ? 'Reporte_Caja_' : 'Corte_' . $session['id_caja'] . '_') . $report['fecha'];
        $pdf->stream($name . '.pdf', ['Attachment' => false]);
        exit;
    }
}
