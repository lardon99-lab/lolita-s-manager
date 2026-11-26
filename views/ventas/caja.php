<?php
use App\Security\Csrf;
use App\Services\CashClosingService;
use App\Support\View;

extract($cashPage, EXTR_SKIP);
$actionUrl = 'api.php?resource=caja&action=change';
$cashUrl = 'index.php?view=caja&sucursal=' . (int) $branchId;
$folio = (int) ($session['id_caja'] ?? 0);
$state = $session['estado'] ?? '';
$report = $session['informe'] ?? null;
$cut = $report['corte'] ?? null;
$formInput = ($flash['id'] ?? null) === $folio ? ($flash['input'] ?? []) : [];
$inputValue = static fn (mixed $value, string $fallback = ''): string => is_string($value) ? $value : $fallback;
$money = static fn (int $cents): string => 'L. ' . View::money($cents / 100);
$decimal = static fn (int $cents): string => View::money($cents / 100, false);
$allowCents = $report && CashClosingService::allowsCents($report);
$inputMode = $allowCents ? 'decimal' : 'numeric';
$amountPattern = $allowCents ? '[0-9]{1,10}([.,][0-9]{1,2})?' : '[0-9]{1,10}';
$cashFields = static function (string $operation) use ($branchId, $folio): void { ?>
    <?= Csrf::field() ?>
    <input type="hidden" name="operacion" value="<?= e($operation) ?>">
    <input type="hidden" name="sucursal" value="<?= (int) $branchId ?>">
    <input type="hidden" name="id_caja" value="<?= $folio ?>">
<?php };
?>

<main class="container-fluid app-page p-2 p-md-4">
    <header class="app-page-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-cash-register"></i></span>
            <div class="app-page-header__copy">
                <h1 class="app-page-header__title">Caja</h1>
                <p class="app-page-header__subtitle">Apertura, corte y entrega de efectivo por sucursal.</p>
            </div>
        </div>
        <div class="app-page-header__actions">
            <a class="btn btn-outline-primary" href="index.php?view=ventas-historial<?= $branch ? '&amp;sucursal=' . (int) $branchId : '' ?>">
                <i class="fa-solid fa-clock-rotate-left me-1" aria-hidden="true"></i> Historial de ventas
            </a>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if (!$branch): ?>
        <div class="alert alert-warning">No tienes sucursales asignadas para consultar Caja.</div>
    <?php else: ?>
        <form class="app-toolbar row g-2 align-items-end mx-0" method="get" action="index.php">
            <input type="hidden" name="view" value="caja">
            <div class="col-12 col-sm-auto">
                <label class="form-label fw-semibold" for="cash-branch">Sucursal</label>
                <select class="form-select" id="cash-branch" name="sucursal">
                    <?php foreach ($branches as $id => $item): ?>
                        <option value="<?= (int) $id ?>" <?= $id === $branchId ? 'selected' : '' ?>>
                            <?= e($item['nombre_sucursal']) ?><?= $item['estado'] !== 'Activa' ? ' (inactiva)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-auto d-grid"><button type="submit" class="btn btn-outline-primary">Ver jornada actual</button></div>
        </form>

        <?php if ($canClose && $canOpen): ?>
        <section class="app-panel mb-3">
            <div class="card-body p-3 p-md-4">
                <h2 class="h5 fw-semibold">Abrir la caja de hoy</h2>
                <form action="<?= e($actionUrl) ?>" method="post">
                    <?php $cashFields('abrir'); ?>
                    <div class="form-check my-3">
                        <input class="form-check-input" type="checkbox" name="confirmar_fondo" value="1" id="cash-fund" required>
                        <label class="form-check-label" for="cash-fund">Confirmo que hay L. 1,000 de fondo en la gaveta.</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Abrir caja</button>
                </form>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($session): ?>
        <section class="app-panel mb-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-semibold mb-1">Jornada #<?= $folio ?> · <?= date('d/m/Y', strtotime($session['fecha_operativa'])) ?></h2>
                        <p class="text-muted small mb-0">Abierta por <?= e($session['nombre_apertura']) ?> · <?= date('g:i a', strtotime($session['fecha_apertura'])) ?></p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border"><?= e($state) ?></span>
                </div>

                <?php if ($state === 'Abierta' && $canClose): ?>
                    <?php if ($canCount): ?>
                        <p>Al iniciar el conteo, se pausarán los cobros en efectivo y los gastos de esta sucursal.</p>
                        <form action="<?= e($actionUrl) ?>" method="post">
                            <?php $cashFields('contar'); ?>
                            <button type="submit" class="btn btn-primary">Iniciar corte de caja</button>
                        </form>
                    <?php else: ?>
                        <p class="mb-2">El corte se habilita a las <strong>5:50 p. m., hora de Honduras</strong>.</p>
                        <a class="btn btn-outline-primary" href="<?= e($cashUrl) ?>">Actualizar jornada</a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($report): ?>
                    <a class="btn btn-outline-primary mb-3" target="_blank" rel="noopener"
                        href="api.php?resource=caja&amp;action=pdf&amp;sucursal=<?= (int) $branchId ?>&amp;id_caja=<?= $folio ?>">
                        <i class="fa-solid fa-file-pdf me-1" aria-hidden="true"></i> <?= $cut ? 'Ver PDF del corte' : 'Ver PDF para preparar los sobres' ?>
                    </a>
                    <dl class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-2 mb-3">
                        <?php foreach ([
                            'Fondo en la gaveta' => $report['fondo'],
                            'Cobrado en efectivo' => array_sum(array_column($report['categorias'], 'cobrado')),
                            'Gastos de caja' => $report['gastos_centavos'],
                            'Efectivo esperado' => $report['esperado'],
                        ] as $label => $amount): ?>
                            <div class="col"><div class="bg-light border rounded-3 h-100 p-2">
                                <dt class="small text-muted"><?= e($label) ?></dt><dd class="fw-semibold mb-0"><?= $money($amount) ?></dd>
                            </div></div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>

                <?php if ($state === 'En conteo' && $canClose): ?>
                    <?php if (!empty($report['incidencia'])): ?>
                        <div class="alert alert-warning">Conteo por debajo del fondo fijo (<?= $money($report['incidencia']['contado']) ?>). <?= e($report['incidencia']['nota']) ?></div>
                    <?php endif; ?>
                    <form action="<?= e($actionUrl) ?>" method="post" data-cash-count="<?= (int) $report['fondo'] ?>">
                        <?php $cashFields('cerrar'); ?>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold" for="cash-counted">Efectivo contado (L.)</label>
                                <input class="form-control" type="text" inputmode="<?= $inputMode ?>" pattern="<?= $amountPattern ?>" maxlength="<?= $allowCents ? 13 : 10 ?>" id="cash-counted" name="contado" required
                                    value="<?= e($inputValue($formInput['contado'] ?? null)) ?>" aria-describedby="cash-counted-help">
                                <small class="text-muted" id="cash-counted-help">Cuenta todo el efectivo de la gaveta, incluido el fondo.</small>
                            </div>
                        </div>
                        <h3 class="h6 fw-semibold">Sobres por categoría</h3>
                        <p class="small text-muted">Deja L. 1,000 en la gaveta. Registra lo que colocas en cada sobre y explica cómo descontaste los gastos si los hubo.</p>
                        <div class="d-grid gap-2 mb-3">
                            <?php foreach ($report['categorias'] as $id => $category): ?>
                            <div class="row g-2 align-items-center border rounded-3 p-2 mx-0">
                                <div class="col-12 col-sm">
                                    <label for="cash-envelope-<?= (int) $id ?>" class="fw-semibold d-block text-break"><?= e($category['nombre']) ?></label>
                                    <small class="text-muted">Cobrado en efectivo <?= $money($category['cobrado']) ?></small>
                                </div>
                                <div class="col-12 col-sm-5 col-xl-3">
                                    <div class="input-group">
                                        <span class="input-group-text">L.</span>
                                        <input class="form-control" type="text" inputmode="<?= $inputMode ?>" pattern="<?= $amountPattern ?>" maxlength="<?= $allowCents ? 13 : 10 ?>" data-cash-envelope
                                            id="cash-envelope-<?= (int) $id ?>" name="sobres[<?= (int) $id ?>]"
                                            value="<?= e($inputValue($formInput['sobres'][$id] ?? null, $decimal($category['cobrado']))) ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="small fw-semibold" data-cash-distribution role="status"></p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="cash-closing-note">Observación</label>
                            <textarea class="form-control" id="cash-closing-note" name="nota" rows="2" maxlength="500"
                                aria-describedby="cash-closing-help"><?= e($inputValue($formInput['nota'] ?? null)) ?></textarea>
                            <small class="text-muted" id="cash-closing-help">Requerida cuando hay un faltante, sobrante o ajuste en los sobres.</small>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-success">Guardar corte y registrar retiro</button>
                            <button type="submit" class="btn btn-outline-secondary" form="cash-cancel">Cancelar conteo</button>
                        </div>
                    </form>
                    <form id="cash-cancel" action="<?= e($actionUrl) ?>" method="post">
                        <?php $cashFields('cancelar'); ?>
                    </form>
                <?php endif; ?>

                <?php if ($cut): ?>
                    <h3 class="h6 fw-semibold">Retiro registrado</h3>
                    <p class="small text-muted mb-2">
                        <?= e($session['nombre_cierre']) ?> · <?= date('d/m/Y g:i a', strtotime($session['fecha_cierre'])) ?>
                        · Contado <?= $money($cut['contado']) ?> · Retiro <?= $money($cut['retiro']) ?>
                        · Diferencia <?= $money($cut['diferencia']) ?>
                    </p>
                    <ul class="list-group mb-3">
                        <?php foreach ($report['categorias'] as $category): ?>
                            <li class="list-group-item d-flex flex-wrap justify-content-between gap-2"><span class="text-break"><?= e($category['nombre']) ?></span><strong><?= $money($category['entregado']) ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($cut['nota'] !== ''): ?><p class="text-break"><?= e($cut['nota']) ?></p><?php endif; ?>
                    <?php if ($session['fecha_recepcion']): ?>
                        <p class="mb-0 text-break">Recibido por <?= e($session['nombre_recepcion']) ?> · L. <?= View::money($session['monto_recibido']) ?>
                            · <?= date('d/m/Y g:i a', strtotime($session['fecha_recepcion'])) ?>. <?= e($session['nota_recepcion'] ?? '') ?></p>
                    <?php else: ?>
                        <p class="text-muted">Recepción pendiente de confirmar.</p>
                        <?php if ($canReceive): ?>
                        <form class="row g-3" action="<?= e($actionUrl) ?>" method="post">
                            <?php $cashFields('recibir'); ?>
                            <div class="col-12 col-sm-5">
                                <label class="form-label fw-semibold" for="cash-received">Efectivo recibido (L.)</label>
                                <input class="form-control" type="text" inputmode="<?= $inputMode ?>" pattern="<?= $amountPattern ?>" maxlength="<?= $allowCents ? 13 : 10 ?>" id="cash-received" name="recibido" required
                                    value="<?= e($inputValue($formInput['recibido'] ?? null, $decimal($cut['retiro']))) ?>">
                            </div>
                            <div class="col-12 col-sm-7">
                                <label class="form-label fw-semibold" for="cash-receipt-note">Observación de recepción</label>
                                <input class="form-control" id="cash-receipt-note" name="nota" maxlength="500"
                                    value="<?= e($inputValue($formInput['nota'] ?? null)) ?>">
                            </div>
                            <div class="col-12"><button type="submit" class="btn btn-primary">Confirmar recepción</button></div>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
        <?php elseif (!$canClose || !$canOpen): ?>
            <p class="text-muted">Todavía no hay jornadas para esta sucursal.</p>
        <?php endif; ?>

        <?php if ($history): ?>
        <section class="app-panel mb-3">
            <div class="card-body p-3 p-md-4">
                <h2 class="h5 fw-semibold">Cortes anteriores</h2>
                <ul class="list-group">
                    <?php foreach ($history as $row): ?>
                    <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span>#<?= (int) $row['id_caja'] ?> · <?= date('d/m/Y', strtotime($row['fecha_operativa'])) ?>
                            · <?= $row['fecha_recepcion'] ? 'Recibido' : 'Recepción pendiente' ?></span>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e($cashUrl . '&id_caja=' . (int) $row['id_caja']) ?>">Ver corte</a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
