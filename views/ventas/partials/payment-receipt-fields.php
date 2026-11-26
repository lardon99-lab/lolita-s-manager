<?php
$isCardReceipt = $receiptMethod === 'Tarjeta';
$receiptSuffix = $isCardReceipt ? 'tarjeta' : 'transferencia';
?>
<?php if ($isCardReceipt): ?>
    <p class="small text-muted mb-3">Usa el comprobante aprobado del datáfono. Evita ingresar el número de tarjeta, el CVV o el PIN.</p>
<?php else: ?>
    <p class="small text-muted mb-3">Usa el importe y la referencia del comprobante bancario.</p>
<?php endif; ?>
<input type="hidden" name="intento_<?= $receiptSuffix ?>" value="<?= bin2hex(random_bytes(16)) ?>">
<div class="row g-3">
    <?php if ($isCardReceipt): $receiptDate = date('Y-m-d'); ?>
    <label class="col-12 col-sm-6">
        <span class="form-label small fw-semibold d-block">Identificador de terminal</span>
        <input class="form-control" type="text" name="terminal_tarjeta" maxlength="32" pattern="[a-zA-Z0-9][a-zA-Z0-9._\/\-]*" autocomplete="off" autocapitalize="characters" spellcheck="false" required>
    </label>
    <label class="col-12 col-sm-6">
        <span class="form-label small fw-semibold d-block">Fecha del comprobante</span>
        <input class="form-control" type="date" name="fecha_tarjeta" value="<?= e($receiptDate) ?>" max="<?= e($receiptDate) ?>" required>
    </label>
    <?php endif; ?>
    <label class="col-12 col-sm-6">
        <span class="form-label small fw-semibold d-block">Referencia de la operación</span>
        <input class="form-control" type="text" name="referencia_<?= $receiptSuffix ?>" maxlength="64" pattern="[a-zA-Z0-9][a-zA-Z0-9._\/\-]*" autocomplete="off" autocapitalize="characters" spellcheck="false" required>
    </label>
    <label class="col-12 col-sm-6">
        <span class="form-label small fw-semibold d-block"><?= $isCardReceipt ? 'Importe aprobado (L.)' : 'Monto depositado (L.)' ?></span>
        <input class="form-control" type="text" name="monto_<?= $receiptSuffix ?>" data-receipt-amount inputmode="numeric" maxlength="8" pattern="[0-9]{1,8}" placeholder="0" autocomplete="off" required>
    </label>
</div>
