<?php
$cardPageUrl = static fn (int $page): string => 'index.php?' . http_build_query(
    ['view' => 'ventas-historial', 'pagina_tarjeta' => $page] + $filtros
) . '#pagos-tarjeta';
?>
<section class="app-panel mt-3 overflow-hidden" id="pagos-tarjeta" aria-labelledby="cardPaymentsTitle">
    <header class="p-3 px-md-4 border-bottom">
        <h3 class="h6 fw-bold mb-1" id="cardPaymentsTitle"><i class="fa-solid fa-credit-card me-2 text-primary" aria-hidden="true"></i>Cobros con tarjeta</h3>
        <p class="small text-muted mb-0">Compara las referencias registradas por el personal con el cierre del datáfono.</p>
    </header>
    <?php if ($cardPayments['rows'] === []): ?>
        <p class="p-3 text-center text-muted mb-0">No hay cobros con tarjeta en esta página.</p>
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
                    <?php foreach ($cardPayments['rows'] as $cardRow): ?>
                    <tr>
                        <td data-label="Operación" class="fw-semibold"><?= e($cardRow['origen']) ?> #<?= (int) $cardRow['id_operacion'] ?></td>
                        <td data-label="Sucursal / Operador"><div><?= e($cardRow['nombre_sucursal']) ?></div><small class="text-muted"><?= e($cardRow['operador']) ?></small></td>
                        <td data-label="Registro"><?= e(date('d/m/Y g:i a', strtotime($cardRow['fecha']))) ?></td>
                        <td data-label="Comprobante">
                            <?php if ($cardRow['referencia'] !== null): ?>
                                <div class="fw-semibold text-break"><?= e($cardRow['referencia']) ?></div>
                                <small class="text-muted text-break"><?= e($cardRow['terminal']) ?> · <?= e(date('d/m/Y', strtotime($cardRow['fecha_comprobante']))) ?></small>
                            <?php else: ?>
                                <span class="small text-muted">Sin comprobante registrado</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Importe" class="text-end fw-semibold">L. <?= number_format((float) $cardRow['monto'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php if ($cardPage > 1 || $cardPayments['has_next']): ?>
        <nav class="d-flex align-items-center justify-content-end gap-2 border-top p-3" aria-label="Páginas de cobros con tarjeta">
            <?php if ($cardPage > 1): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e($cardPageUrl($cardPage - 1)) ?>">Anterior</a><?php endif; ?>
            <span class="small text-muted">Página <?= $cardPage ?></span>
            <?php if ($cardPayments['has_next']): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e($cardPageUrl($cardPage + 1)) ?>">Siguiente</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
