<?php
$receiptPageUrl = static fn (int $page): string => 'index.php?' . http_build_query(
    ['view' => 'ventas-historial', 'pagina_comprobantes' => $page] + $filtros
) . '#pagos-electronicos';
?>
<section class="app-panel mt-3 overflow-hidden" id="pagos-electronicos" aria-labelledby="paymentReceiptsTitle">
    <header class="p-3 px-md-4 border-bottom">
        <h3 class="h6 fw-bold mb-1" id="paymentReceiptsTitle"><i class="fa-solid fa-receipt me-2 text-primary" aria-hidden="true"></i>Cobros con tarjeta y transferencia</h3>
        <p class="small text-muted mb-0">Consulta los importes y referencias registrados por el personal.</p>
    </header>
    <?php if ($paymentReceipts['rows'] === []): ?>
        <p class="p-3 text-center text-muted mb-0">No hay cobros con tarjeta ni transferencia en esta página.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 mobile-card-table">
                <thead class="table-light small text-muted">
                    <tr>
                        <th scope="col">Operación</th>
                        <th scope="col">Sucursal / Operador</th>
                        <th scope="col">Registro</th>
                        <th scope="col">Comprobante</th>
                        <th scope="col" class="text-end">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paymentReceipts['rows'] as $receipt): ?>
                    <tr>
                        <td data-label="Operación"><div class="fw-semibold"><?= e($receipt['origen']) ?> #<?= (int) $receipt['id_operacion'] ?></div><small class="text-muted"><?= e($receipt['metodo_pago']) ?></small></td>
                        <td data-label="Sucursal / Operador"><div><?= e($receipt['nombre_sucursal']) ?></div><small class="text-muted"><?= e($receipt['operador']) ?></small></td>
                        <td data-label="Registro"><?= e(date('d/m/Y g:i a', strtotime($receipt['fecha']))) ?></td>
                        <td data-label="Comprobante">
                            <?php if ($receipt['referencia'] !== null): ?>
                                <div class="fw-semibold text-break"><?= e($receipt['referencia']) ?></div>
                                <?php if ($receipt['terminal'] !== null && $receipt['fecha_comprobante'] !== null): ?>
                                    <small class="text-muted text-break"><?= e($receipt['terminal']) ?> · <?= e(date('d/m/Y', strtotime($receipt['fecha_comprobante']))) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="small text-muted">Sin comprobante registrado</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Importe" class="text-end fw-semibold">L. <?= \App\Support\View::money($receipt['monto']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php if ($receiptPage > 1 || $paymentReceipts['has_next']): ?>
        <nav class="d-flex align-items-center justify-content-end gap-2 border-top p-3" aria-label="Páginas de comprobantes electrónicos">
            <?php if ($receiptPage > 1): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e($receiptPageUrl($receiptPage - 1)) ?>">Anterior</a><?php endif; ?>
            <span class="small text-muted">Página <?= $receiptPage ?></span>
            <?php if ($paymentReceipts['has_next']): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e($receiptPageUrl($receiptPage + 1)) ?>">Siguiente</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
