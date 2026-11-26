<?php foreach (['Tarjeta', 'Transferencia'] as $receiptMethod): ?>
    <template id="receipt-<?= e($receiptMethod) ?>">
        <?php include __DIR__ . '/payment-receipt-fields.php'; ?>
    </template>
<?php endforeach; ?>
