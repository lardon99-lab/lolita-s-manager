(() => {
    'use strict';

    const printer = window.PosPrinter;

    async function reprint(saleId) {
        try {
            await printer.printSale(saleId);
            await Swal.fire({ title: 'Copia enviada a la impresora', icon: 'success' });
        } catch (error) {
            await Swal.fire({ title: 'No se pudo enviar el ticket', text: error.message, icon: 'warning' });
        }
    }

    async function finishSale(sale, branch) {
        const title = 'Venta #' + sale.id_venta + ' registrada';
        if (!sale.nueva_venta || !printer.settings(branch)) {
            await Swal.fire({ title, text: sale.message, icon: 'success', timer: 1500, showConfirmButton: false });
            return;
        }

        Swal.fire({
            title,
            text: 'Enviando el ticket. Si QZ Tray solicita permiso, responde en su ventana.',
            allowEscapeKey: false,
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });
        try {
            await printer.printSale(sale.id_venta, { copy: false, openDrawer: true });
            await Swal.fire({ title, text: 'Ticket enviado a la impresora.', icon: 'success', timer: 1500, showConfirmButton: false });
        } catch (error) {
            const result = await Swal.fire({
                title,
                text: error.message + ' Puedes reimprimir una copia desde el historial.',
                icon: 'warning',
                showDenyButton: true,
                confirmButtonText: 'Continuar',
                denyButtonText: 'Reimprimir copia',
            });
            if (result.isDenied) await reprint(sale.id_venta);
        }
    }

    function setup(modal) {
        const branch = modal.dataset.branch;
        const form = modal.querySelector('form');
        const select = form.elements.namedItem('printer');
        const columns = form.elements.namedItem('columns');
        const verified = form.elements.namedItem('verified');
        const drawer = form.elements.namedItem('drawer');
        const status = modal.querySelector('#posDevicesStatus');
        const save = form.querySelector('button[type="submit"]');
        const search = form.querySelector('[data-pos-action="search"]');
        const printTest = form.querySelector('[data-pos-action="print"]');
        const drawerTest = form.querySelector('[data-pos-action="drawer"]');
        const clear = form.querySelector('[data-pos-action="clear"]');
        let saved = printer.settings(branch);
        let testSent = false;
        let drawerTestSent = false;

        function setStatus(message, error = false) {
            status.textContent = message;
            status.classList.toggle('text-danger', error);
            status.classList.toggle('text-muted', !error);
        }

        function updateControls(busy = false) {
            search.disabled = busy;
            select.disabled = busy;
            columns.disabled = busy;
            clear.disabled = busy;
            printTest.disabled = busy || !select.value;
            verified.disabled = busy || !testSent;
            if (drawer) {
                drawer.disabled = busy || !verified.checked || !drawerTestSent;
                drawerTest.disabled = busy || !verified.checked;
            }
            save.disabled = busy || !select.value || !verified.checked;
        }

        function loadSelection() {
            const matches = select.value !== '' && select.value === saved?.printer;
            testSent = matches && Number(columns.value) === saved.columns;
            drawerTestSent = matches && saved.drawer === true;
            verified.checked = testSent;
            if (drawer) drawer.checked = drawerTestSent;
            updateControls();
        }

        function setPrinters(names, current) {
            select.replaceChildren(new Option('Selecciona una impresora', ''));
            for (const name of names) select.add(new Option(name, name));
            select.value = names.includes(current) ? current : '';
            loadSelection();
        }

        const initial = saved ? [saved.printer] : [];
        if (saved) columns.value = String(saved.columns);
        setPrinters(initial, saved?.printer);
        if (saved) setStatus('Impresora configurada: ' + saved.printer);
        select.addEventListener('change', loadSelection);
        columns.addEventListener('change', () => {
            testSent = false;
            verified.checked = false;
            updateControls();
        });
        verified.addEventListener('change', () => updateControls());

        async function run(action) {
            updateControls(true);
            setStatus('Conectando con QZ Tray…');
            try {
                await action();
            } catch (error) {
                setStatus(error.message, true);
            } finally {
                updateControls();
            }
        }

        search.addEventListener('click', () => run(async () => {
            const current = select.value;
            const names = await printer.printers();
            setPrinters(names, current);
            setStatus(names.length ? 'Selecciona la RPT004 en la lista.' : 'Windows no reportó ninguna cola de impresión.');
        }));
        printTest.addEventListener('click', () => run(async () => {
            testSent = false;
            verified.checked = false;
            await printer.testPrint(select.value, columns.value);
            testSent = true;
            setStatus('Prueba enviada. Comprueba ambos bordes, el final del ticket y el estado de la gaveta.');
        }));
        drawerTest?.addEventListener('click', () => run(async () => {
            drawerTestSent = false;
            drawer.checked = false;
            await printer.testDrawer(select.value);
            drawerTestSent = true;
            setStatus('Prueba enviada. Marca la casilla si la gaveta se abrió.');
        }));
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!select.value || !verified.checked) return;
            const value = {
                printer: select.value,
                columns: Number(columns.value),
                drawer: drawer
                    ? drawerTestSent && drawer.checked
                    : select.value === saved?.printer && saved.drawer === true,
            };
            try {
                printer.saveSettings(branch, value);
                saved = value;
                setStatus('Configuración guardada para este navegador.');
            } catch {
                setStatus('El navegador no permite guardar la configuración local.', true);
            }
        });
        clear.addEventListener('click', () => {
            try {
                printer.saveSettings(branch, null);
                saved = null;
                select.value = '';
                loadSelection();
                setStatus('Impresión automática desactivada en este equipo.');
            } catch {
                setStatus('El navegador no permite cambiar la configuración local.', true);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('posDevicesModal');
        if (modal) setup(modal);
        document.querySelectorAll('[data-sale-reprint]').forEach((button) => {
            button.addEventListener('click', async () => {
                button.disabled = true;
                try {
                    await reprint(button.dataset.saleReprint);
                } finally {
                    button.disabled = false;
                }
            });
        });
    });

    window.PosDevices = { finishSale };
})();
