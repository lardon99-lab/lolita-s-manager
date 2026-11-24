<?php $receiptDate = date('Y-m-d'); ?>
<p class="small text-muted mb-3">Usa el comprobante aprobado del datáfono. Evita ingresar el número de tarjeta, el CVV o el PIN.</p>
<input type="hidden" name="intento_tarjeta" value="<?= bin2hex(random_bytes(16)) ?>">
<div class="row g-3">
    <label class="col-12 col-sm-6">
        <span class="form-label small fw-semibold d-block">Identificador de terminal</span>
        <input class="form-control" type="text" name="terminal_tarjeta" maxlength="32" pattern="[a-zA-Z0-9][a-zA-Z0-9._\/\-]*" autocomplete="off" autocapitalize="characters" spellcheck="false" required>
    </label>
    <label class="col-12 col-sm-6">
        <span class="form-label small fw-semibold d-block">Referencia de la operación</span>
        <input class="form-control" type="text" name="referencia_tarjeta" maxlength="64" pattern="[a-zA-Z0-9][a-zA-Z0-9._\/\-]*" autocomplete="off" autocapitalize="characters" spellcheck="false" required>
    </label>
    <label class="col-12 col-sm-6">
        <span class="form-label small fw-semibold d-block">Fecha del comprobante</span>
        <input class="form-control" type="date" name="fecha_tarjeta" value="<?= e($receiptDate) ?>" max="<?= e($receiptDate) ?>" required>
    </label>
    <label class="col-12 col-sm-6">
        <span class="form-label small fw-semibold d-block">Importe aprobado (L.)</span>
        <input class="form-control" type="text" name="monto_tarjeta" inputmode="decimal" maxlength="11" pattern="[0-9]{1,8}([.,][0-9]{1,2})?" placeholder="0.00" autocomplete="off" required>
    </label>
</div>
