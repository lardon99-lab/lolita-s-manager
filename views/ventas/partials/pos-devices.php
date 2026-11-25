<div class="modal fade" id="posDevicesModal" tabindex="-1" aria-labelledby="posDevicesTitle" aria-hidden="true" data-branch="<?= (int) $id_sucursal_user ?>">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <form class="modal-content" id="posDevicesForm">
            <header class="modal-header">
                <h2 class="modal-title h5 mb-0" id="posDevicesTitle">Impresora y gaveta</h2>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </header>
            <div class="modal-body">
                <p class="small text-muted">Abre QZ Tray en este equipo y selecciona la cola de Windows de la RPT004. La configuración se guarda en este navegador para la sucursal seleccionada.</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="posPrinterName">Impresora de tickets</label>
                    <select class="form-select" id="posPrinterName" name="printer" data-native-select="true" required>
                        <option value="">Selecciona una impresora</option>
                    </select>
                    <button class="btn btn-outline-primary mt-2" type="button" data-pos-action="search">
                        <i class="fa-solid fa-magnifying-glass me-2" aria-hidden="true"></i>Buscar impresoras
                    </button>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="posTicketWidth">Ancho del ticket</label>
                    <select class="form-select" id="posTicketWidth" name="columns" aria-describedby="posTicketWidthHelp">
                        <option value="38">Estándar · 38 columnas</option>
                        <option value="32">Compacto · 32 columnas</option>
                    </select>
                    <small class="form-text d-block" id="posTicketWidthHelp">Si la prueba pierde su borde derecho, elige el formato compacto.</small>
                </div>
                <div class="app-panel p-3 mb-3">
                    <p class="small mb-2">Comprueba que la RPT004 tenga desactivada la apertura de la gaveta al cortar papel (SW-6 en OFF).</p>
                    <button class="btn btn-outline-secondary mb-2" type="button" data-pos-action="print" disabled>Imprimir prueba</button>
                    <label class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="verified" required disabled>
                        <span class="form-check-label">Se imprimieron los dos bordes de la línea y «FIN DE PRUEBA». La gaveta siguió cerrada.</span>
                    </label>
                </div>
                <?php if (in_array((int) ($_SESSION['id_rol'] ?? 0), [\App\Security\Auth::ADMIN, \App\Security\Auth::SUPERUSER], true)): ?>
                <div class="app-panel p-3">
                    <button class="btn btn-outline-secondary mb-2" type="button" data-pos-action="drawer" disabled>Probar apertura</button>
                    <label class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="drawer" disabled>
                        <span class="form-check-label">La prueba abrió la gaveta. Activar la apertura en ventas en efectivo.</span>
                    </label>
                </div>
                <?php endif; ?>
                <p class="small mt-3 mb-0" role="status" aria-live="polite" id="posDevicesStatus"></p>
            </div>
            <footer class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-pos-action="clear">Desactivar en este equipo</button>
                <button class="btn btn-primary" type="submit" disabled>Guardar configuración</button>
            </footer>
        </form>
    </div>
</div>
