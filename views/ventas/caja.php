<?php
use App\Security\Csrf;
use App\Services\CashClosingService;
use App\Services\CashDrawerSession;
use App\Support\View;

extract($cashPage, EXTR_SKIP);
$actionUrl = 'api.php?resource=caja&action=change';
$cashUrl = 'index.php?view=caja&sucursal=' . (int) $branchId;
$folio = (int) ($session['id_caja'] ?? 0);
$state = $session['estado'] ?? '';
$reopening = $state === 'Cerrada' && $session['fecha_operativa'] === $today;
$report = $session['informe'] ?? null;
$cut = $report['corte'] ?? null;
$formInput = ($flash['id'] ?? null) === $folio ? ($flash['input'] ?? []) : [];
$inputValue = static fn (mixed $value): string => is_string($value) ? $value : '';
$money = static fn (int $cents): string => 'L. ' . View::money($cents / 100);
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
                <p class="app-page-header__subtitle">Aperturas y cortes de efectivo por sucursal.</p>
                <?php if ($canClose): ?>
                    <p class="small text-muted mt-2 mb-0">Fondo operativo de referencia <strong><?= $money(CashDrawerSession::OPERATING_FUND_CENTS) ?></strong></p>
                <?php endif; ?>
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
                <h2 class="h5 fw-semibold"><?= $reopening ? '¿Necesitas seguir vendiendo?' : 'Abrir la caja de hoy' ?></h2>
                <?php if ($reopening): ?>
                    <p class="text-muted">Los próximos cobros en efectivo irán a un nuevo corte.</p>
                <?php endif; ?>
                <form action="<?= e($actionUrl) ?>" method="post">
                    <?php $cashFields('abrir'); ?>
                    <button type="submit" class="btn btn-primary"><?= $reopening ? 'Reabrir caja' : 'Abrir caja' ?></button>
                </form>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($session): ?>
        <section class="app-panel mb-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-semibold mb-1"><?= $cut ? 'Corte' : 'Apertura' ?> #<?= $folio ?> · <?= date('d/m/Y', strtotime($session['fecha_operativa'])) ?></h2>
                        <p class="text-muted small mb-0">Abierta por <?= e($session['nombre_apertura']) ?> · <?= date('g:i a', strtotime($session['fecha_apertura'])) ?></p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border"><?= e($state) ?></span>
                </div>

                <?php if ($canClose && !$canCount && in_array($state, ['Abierta', 'En conteo'], true)): ?>
                    <p class="mb-2">El corte se habilita a las <strong><?= e(CashDrawerSession::closingTimeLabel()) ?>, hora de Honduras</strong>.</p>
                    <a class="btn btn-outline-primary mb-3" href="<?= e($cashUrl) ?>">Actualizar jornada</a>
                <?php elseif ($state === 'Abierta' && $canClose): ?>
                    <p>Al iniciar el conteo, se pausarán los cobros en efectivo y los gastos de esta sucursal.</p>
                    <form action="<?= e($actionUrl) ?>" method="post">
                        <?php $cashFields('contar'); ?>
                        <button type="submit" class="btn btn-primary">Iniciar corte de caja</button>
                    </form>
                <?php endif; ?>

                <?php if ($report): ?>
                    <a class="btn btn-outline-primary mb-3" target="_blank" rel="noopener"
                        href="api.php?resource=caja&amp;action=pdf&amp;sucursal=<?= (int) $branchId ?>&amp;id_caja=<?= $folio ?>">
                        <i class="fa-solid fa-file-pdf me-1" aria-hidden="true"></i> Ver PDF del corte
                    </a>
                    <dl class="row row-cols-1 row-cols-sm-3 g-2 mb-3">
                        <?php foreach ([
                            'Efectivo cobrado' => $report['efectivo_centavos'],
                            'Gastos de esta apertura' => $report['gastos_centavos'],
                            'Efectivo neto esperado' => $report['esperado'],
                        ] as $label => $amount): ?>
                            <div class="col"><div class="bg-light border rounded-3 h-100 p-2">
                                <dt class="small text-muted"><?= e($label) ?></dt><dd class="fw-semibold mb-0"><?= $money($amount) ?></dd>
                            </div></div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>

                <?php if ($state === 'En conteo' && $canClose): ?>
                    <form action="<?= e($actionUrl) ?>" method="post" data-cash-expected="<?= (int) $report['esperado'] ?>">
                        <?php $cashFields('cerrar'); ?>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label fw-semibold" for="cash-counted">Efectivo contado de esta apertura (L.)</label>
                                <input class="form-control" type="text" inputmode="<?= $inputMode ?>" pattern="<?= $amountPattern ?>" maxlength="<?= $allowCents ? 13 : 10 ?>" id="cash-counted" name="contado" required
                                    value="<?= e($inputValue($formInput['contado'] ?? null)) ?>" aria-describedby="cash-counted-help">
                                <small class="text-muted" id="cash-counted-help">Registra el efectivo que corresponde a los movimientos de esta apertura.</small>
                            </div>
                        </div>
                        <p class="small fw-semibold" data-cash-difference role="status"></p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="cash-closing-note">Observación</label>
                            <textarea class="form-control" id="cash-closing-note" name="nota" rows="2" maxlength="500"
                                aria-describedby="cash-closing-help"><?= e($inputValue($formInput['nota'] ?? null)) ?></textarea>
                            <small class="text-muted" id="cash-closing-help">Requerida cuando hay un faltante o un sobrante.</small>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-success" <?= $canCount ? '' : 'disabled' ?>>Guardar corte</button>
                            <button type="submit" class="btn btn-outline-secondary" form="cash-cancel"><?= $session['fecha_operativa'] === $today ? 'Volver a vender' : 'Cancelar conteo' ?></button>
                        </div>
                    </form>
                    <form id="cash-cancel" action="<?= e($actionUrl) ?>" method="post">
                        <?php $cashFields('cancelar'); ?>
                    </form>
                <?php endif; ?>

                <?php if ($cut): ?>
                    <h3 class="h6 fw-semibold">Corte guardado</h3>
                    <p class="small text-muted mb-2">
                        <?= e($session['nombre_cierre']) ?> · <?= date('d/m/Y g:i a', strtotime($session['fecha_cierre'])) ?>
                        · Contado <?= $money($cut['contado']) ?>
                        · Diferencia <?= $money($cut['diferencia']) ?>
                    </p>
                    <?php if ($cut['nota'] !== ''): ?><p class="text-break"><?= e($cut['nota']) ?></p><?php endif; ?>
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
                        <span>#<?= (int) $row['id_caja'] ?> · <?= date('d/m/Y g:i a', strtotime($row['fecha_cierre'])) ?></span>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e($cashUrl . '&id_caja=' . (int) $row['id_caja']) ?>">Ver corte</a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
