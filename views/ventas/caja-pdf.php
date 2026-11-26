<?php
use App\Support\View;

$corte = $report['corte'] ?? null;
$categories = array_filter($report['categorias'], static fn (array $row): bool => $row['cobrado'] !== 0);
$summary = $session
    ? ['Efectivo cobrado' => $report['efectivo_centavos'] / 100]
    : ['Total cobrado' => array_sum($report['metodos'])] + $report['metodos'];
$summary['Salidas de caja'] = $report['gastos_centavos'] / 100;
$summary['Efectivo neto'] = $report['esperado'] / 100;
if ($corte) {
    $summary['Efectivo contado'] = $corte['contado'] / 100;
    $summary['Diferencia'] = $corte['diferencia'] / 100;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 24px 28px 30px; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 3px solid #D81B60; }
        .titulo { font-size: 20px; font-weight: bold; color: #D81B60; text-transform: uppercase; margin-bottom: 5px; }
        .subtitulo { font-size: 12px; color: #555; }
        .card { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 14px; page-break-inside: auto; }
        .card col:first-child { width: 75%; }
        .card col:last-child { width: 25%; }
        .sales-summary col:nth-child(1) { width: 60%; }
        .sales-summary col:nth-child(2) { width: 18%; }
        .sales-summary col:nth-child(3) { width: 22%; }
        .card thead { display: table-header-group; }
        .card tr { page-break-inside: avoid; page-break-after: auto; }
        .card thead th { 
            background-color: #FCE4EC;
            color: #AD1457;
            padding: 7px 9px;
            text-align: left; 
            font-size: 11px; 
            font-weight: bold; 
            text-transform: uppercase; 
            border-bottom: 2px solid #F48FB1; 
        }
        .card tbody td { 
            padding: 5px 9px;
            border-bottom: 1px solid #eeeeee; 
            color: #444;
            overflow-wrap: break-word;
        }
        .card tbody tr:nth-child(even) { background-color: #fafafa; }
        .card tbody tr:last-child td { border-bottom: 1px solid #cccccc; }
        
        .text-right { text-align: right; font-weight: bold; color: #222; }
        .card thead th.text-right,
        .card tbody td.text-right { text-align: right; }
        .text-muted { color: #888; font-style: italic; }
        .summary { width: 100%; margin-bottom: 16px; border-collapse: collapse; table-layout: fixed; background: #FFF0F5; border: 2px solid #D81B60; }
        .summary td { padding: 8px 4px; text-align: center; border-right: 1px solid #F48FB1; }
        .summary td:last-child { border-right: 0; }
        .summary-label { display: block; margin-bottom: 3px; color: #880E4F; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .summary-value { font-size: 15px; font-weight: bold; color: #222; }
        .summary-value.net { color: #2E7D32; }
        .card tbody tr.category-row td { background: #FFF0F5; color: #880E4F; font-weight: bold; }
        .detail { margin: 0 0 14px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="titulo"><?= $session ? 'Corte de caja #' . (int) $session['id_caja'] : 'Reporte de ventas y caja' ?> - Lolita's Manager</div>
        <div class="subtitulo">
            Jornada del <strong><?= date('d/m/Y', strtotime($report['fecha'])) ?></strong>
            <?php if (!empty($report['sucursal'])): ?> · <?= e($report['sucursal']) ?><?php endif; ?>
            · Datos al <strong><?= date('d/m/Y h:i A', strtotime($report['capturado_en'])) ?></strong>
            <?php if ($session): ?>
                <br>Apertura a las <?= date('g:i a', strtotime($session['fecha_apertura'])) ?>
                · <?= $corte === null ? 'En conteo' : 'Cerrada por ' . e($session['nombre_cierre']) . ' · ' . date('g:i a', strtotime($session['fecha_cierre'])) ?>
            <?php endif; ?>
        </div>
    </div>

    <table class="summary">
        <tr>
            <?php foreach ($summary as $label => $amount): ?>
                <td><span class="summary-label"><?= e($label) ?></span>
                    <span class="summary-value<?= $label === 'Efectivo neto' ? ' net' : '' ?>">L. <?= View::money($amount) ?></span>
                </td>
            <?php endforeach; ?>
        </tr>
    </table>

    <?php if (!empty($corte['nota'])): ?>
    <p class="detail"><strong>Observación del corte</strong> <?= e($corte['nota']) ?></p>
    <?php endif; ?>

    <table class="card">
        <colgroup><col width="75%"><col width="25%"></colgroup>
        <thead><tr><th>Categoría</th><th class="text-right"><?= $session ? 'Efectivo de esta apertura' : 'Efectivo del día' ?></th></tr></thead>
        <tbody>
            <?php if (!$categories): ?><tr><td colspan="2" class="text-muted">Sin cobros en efectivo.</td></tr><?php endif; ?>
            <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= e($category['nombre']) ?></td><td class="text-right">L. <?= View::money($category['cobrado'] / 100) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="card sales-summary">
        <colgroup><col width="60%"><col width="18%"><col width="22%"></colgroup>
        <thead><tr><th><?= $session ? 'Ventas del día hasta el conteo' : 'Resumen de ventas del día' ?></th><th class="text-right">Unidades</th><th class="text-right">Total</th></tr></thead>
        <tbody>
            <?php if (empty($report['ventas'])): ?><tr><td colspan="3" class="text-muted">Sin ventas registradas.</td></tr>
            <?php else: $categoryName = null; foreach ($report['ventas'] as $venta): ?>
            <?php if ($categoryName !== $venta['categoria']): $categoryName = $venta['categoria']; ?>
            <tr class="category-row"><td colspan="3"><?= e($categoryName) ?></td></tr>
            <?php endif; ?>
            <tr><td><?= htmlspecialchars($venta['nombre']) ?></td><td class="text-right"><?= number_format((float) $venta['unidades'], 0) ?></td><td class="text-right">L. <?= \App\Support\View::money($venta['valor']) ?></td></tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <table class="card">
        <colgroup><col width="75%"><col width="25%"></colgroup>
        <thead><tr><th><?= $session ? 'Gastos de esta apertura' : 'Gastos operativos del día' ?></th><th class="text-right">Monto</th></tr></thead>
        <tbody>
            <?php if (empty($report['gastos'])): ?><tr><td colspan="2" class="text-muted">Sin salidas de caja registradas.</td></tr>
            <?php else: foreach ($report['gastos'] as $gasto): ?>
            <tr><td><?= e($gasto['nombre']) ?></td><td class="text-right">L. <?= \App\Support\View::money($gasto['valor']) ?></td></tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <table class="card">
        <colgroup><col width="75%"><col width="25%"></colgroup>
        <thead><tr><th><?= $session ? 'Mermas del día hasta el conteo' : 'Mermas de producto del día' ?></th><th class="text-right">Cantidad</th></tr></thead>
        <tbody>
            <?php if (empty($report['mermas'])): ?><tr><td colspan="2" class="text-muted">Sin mermas de producto registradas.</td></tr>
            <?php else: foreach ($report['mermas'] as $merma): ?>
            <tr><td><?= e($merma['nombre']) ?></td><td class="text-right"><?= (int) $merma['valor'] ?> und.</td></tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <table class="card">
        <colgroup><col width="75%"><col width="25%"></colgroup>
        <thead><tr><th>Inventario restante</th><th class="text-right">Unidades</th></tr></thead>
        <tbody>
            <?php if (empty($report['inventario'])): ?><tr><td colspan="2" class="text-muted">Sin datos de inventario.</td></tr>
            <?php else: $categoryName = null; foreach ($report['inventario'] as $item): ?>
            <?php if ($categoryName !== $item['categoria']): $categoryName = $item['categoria']; ?>
            <tr class="category-row"><td colspan="2"><?= e($categoryName) ?></td></tr>
            <?php endif; ?>
            <tr><td><?= htmlspecialchars($item['nombre']) ?></td><td class="text-right"><?= htmlspecialchars($item['valor']) ?></td></tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</body>
</html>
