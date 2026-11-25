(() => {
    'use strict';

    const libraryUrl = 'https://cdn.jsdelivr.net/npm/qz-tray@2.3.0/qz-tray.js';
    const storageKey = (branch) => 'lolitas.pos.rpt004.' + branch;
    const standardColumns = 38;
    const compactColumns = 32;
    const footer = '\n\x1bd\x06\x1dV\x00'; // Avanza seis líneas antes del corte.
    const drawerPulse = '\x1bp\x00\x32\x78';
    const begin = '\x1b@\x1bM\x00\x1d!\x00\x1bt\x02'; // Fuente normal y página de códigos CP850.
    let library;
    let connection;

    function withDeadline(operation, milliseconds, message) {
        let timer;
        const timeout = new Promise((_, reject) => {
            timer = window.setTimeout(() => reject(new Error(message)), milliseconds);
        });
        return Promise.race([operation, timeout]).finally(() => window.clearTimeout(timer));
    }

    function ticketColumns(value) {
        return Number(value) === compactColumns ? compactColumns : standardColumns;
    }

    function settings(branch) {
        try {
            const value = JSON.parse(localStorage.getItem(storageKey(branch)) || 'null');
            if (typeof value?.printer === 'string' && value.printer) {
                return { ...value, columns: ticketColumns(value.columns) };
            }
        } catch {
            // La venta también funciona en navegadores sin almacenamiento local.
        }
        return null;
    }

    function saveSettings(branch, value) {
        if (value) localStorage.setItem(storageKey(branch), JSON.stringify(value));
        else localStorage.removeItem(storageKey(branch));
    }

    function loadQz() {
        if (window.qz) return Promise.resolve(window.qz);
        if (!library) {
            library = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = libraryUrl;
                script.onload = () => window.qz ? resolve(window.qz) : reject(new Error('No se pudo cargar QZ Tray.'));
                script.onerror = () => {
                    script.remove();
                    reject(new Error('No se pudo cargar el conector de impresión.'));
                };
                document.head.appendChild(script);
            }).catch((error) => {
                library = null;
                throw error;
            });
        }
        return library;
    }

    async function connect() {
        const qz = await loadQz();
        if (!qz.websocket.isActive()) {
            connection ||= qz.websocket.connect({ host: 'localhost', usingSecure: true })
                .catch(() => {
                    throw new Error('Abre QZ Tray y permite que Lolita’s acceda a la conexión local.');
                })
                .finally(() => { connection = null; });
            await withDeadline(connection, 20000, 'QZ Tray no respondió. Revisa los permisos de conexión local.');
        }
        return qz;
    }

    async function printers() {
        const qz = await connect();
        return withDeadline(qz.printers.find(), 30000, 'QZ Tray no confirmó la búsqueda de impresoras.');
    }

    function text(value) {
        // Los datos guardados son texto. Solo este módulo puede insertar comandos ESC/POS.
        return String(value ?? '').normalize('NFC').replace(/[\p{Cc}\p{Cf}\p{Z}]+/gu, ' ').trim();
    }

    function wrap(value, width) {
        const lines = [];
        let line = '';
        for (const word of text(value).split(' ')) {
            if (line && line.length + word.length + 1 > width) {
                lines.push(line);
                line = '';
            }
            if (word.length > width) {
                for (let offset = 0; offset < word.length; offset += width) lines.push(word.slice(offset, offset + width));
            } else if (word) {
                line = line ? line + ' ' + word : word;
            }
        }
        if (line) lines.push(line);
        return lines.join('\n');
    }

    function money(value) {
        return 'L. ' + Number(value).toFixed(2);
    }

    function amountLine(label, value, columns) {
        const amount = money(value);
        const labelWidth = columns - amount.length;
        if (label.length >= labelWidth) return wrap(label, columns) + '\n' + amount.padStart(columns);
        return label.padEnd(labelWidth) + amount;
    }

    function ticketHeader(branch, columns) {
        return [
            '\x1ba\x01\x1bE\x01' + "LOLITA'S" + '\x1bE\x00',
            wrap(branch, columns) + '\n\x1ba\x00',
        ];
    }

    function productLines(product, columns) {
        return [
            wrap(product.nombre, columns),
            amountLine(product.cantidad + ' x ' + money(product.precio), product.subtotal, columns),
        ];
    }

    function saleTicketText(ticket, copy, columns) {
        const separator = '-'.repeat(columns);
        const lines = [
            ...ticketHeader(ticket.sucursal, columns),
            (copy ? 'COPIA - VENTA #' : 'VENTA #') + ticket.id,
            text(ticket.fecha),
            wrap('Pago: ' + ticket.metodo_pago, columns),
            separator,
        ];
        for (const product of ticket.productos) {
            lines.push(...productLines(product, columns));
            for (const option of product.opciones) {
                const surcharge = Number(option.recargo) > 0 ? ' (+' + money(option.recargo) + ' c/u)' : '';
                lines.push(wrap('- ' + option.grupo + ': ' + option.nombre + surcharge, columns));
            }
        }
        lines.push(separator, '\x1bE\x01' + amountLine('TOTAL', ticket.total, columns) + '\x1bE\x00', '', 'Gracias por tu compra.');
        return lines.join('\n');
    }

    function orderTicketText(ticket, columns) {
        const separator = '-'.repeat(columns);
        const lines = [
            ...ticketHeader(ticket.sucursal, columns),
            '\x1bE\x01' + 'PEDIDO #' + ticket.id + '\x1bE\x00',
            wrap('Cliente: ' + ticket.cliente, columns),
            wrap('Entrega programada: ' + ticket.entrega, columns),
            wrap('Estado: ' + ticket.estado, columns),
            separator,
        ];
        for (const product of ticket.productos) {
            lines.push(...productLines(product, columns));
            for (const specification of product.especificaciones) {
                lines.push(wrap('- ' + specification, columns));
            }
        }
        if (text(ticket.observaciones)) {
            lines.push('', wrap('Observaciones: ' + ticket.observaciones, columns));
        }
        lines.push(
            separator,
            amountLine('TOTAL', ticket.total, columns),
            amountLine('Abonado', ticket.abonado, columns),
            '\x1bE\x01' + amountLine('Saldo pendiente', ticket.saldo, columns) + '\x1bE\x00',
            '',
            wrap('Conserva este comprobante.', columns),
        );
        return lines.join('\n');
    }

    async function send(printer, data, jobName) {
        const qz = await connect();
        try {
            const config = qz.configs.create(printer, { encoding: 'Cp850', forceRaw: true, jobName });
            await withDeadline(qz.print(config, [data]), 45000, 'QZ Tray no confirmó el envío.');
        } catch {
            throw new Error('No se confirmó el envío a la impresora. Revisa QZ Tray, la cola de Windows y el papel.');
        }
    }

    function sendTicket(printer, body, jobName, openDrawer = false) {
        return send(printer, begin + (openDrawer ? drawerPulse : '') + '\n' + body + footer, jobName);
    }

    async function fetchTicket(resource, id) {
        const params = new URLSearchParams({ resource, action: 'ticket', id });
        const url = 'api.php?' + params;
        const response = await withDeadline(
            fetch(url, { headers: { Accept: 'application/json' } }),
            15000,
            'No se pudo obtener el ticket a tiempo.'
        );
        const result = await response.json();
        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || 'No se encontró el ticket.');
        }
        return result.ticket;
    }

    function settingsForTicket(ticket) {
        const config = settings(ticket.sucursal_id);
        if (!config) throw new Error('Configura la impresora de esta sucursal en Registrar venta → Impresora y gaveta.');
        return config;
    }

    async function printSale(saleId, options = {}) {
        const ticket = await fetchTicket('ventas', saleId);
        const config = settingsForTicket(ticket);
        const copy = options.copy !== false;
        const open = !copy && options.openDrawer === true && config.drawer === true && ticket.metodo_pago === 'Efectivo';
        await sendTicket(config.printer, saleTicketText(ticket, copy, config.columns), 'Lolitas - Venta ' + ticket.id, open);
    }

    async function printOrder(orderId) {
        const ticket = await fetchTicket('pedidos', orderId);
        const config = settingsForTicket(ticket);
        await sendTicket(config.printer, orderTicketText(ticket, config.columns), 'Lolitas - Pedido ' + ticket.id);
    }

    function testPrint(printer, width) {
        const columns = ticketColumns(width);
        const sample = [
            "LOLITA'S - PRUEBA",
            'Ancho: ' + columns + ' columnas',
            '|' + '-'.repeat(columns - 2) + '|',
            wrap('Ejemplo de producto: café, piña y té de temporada', columns),
            amountLine('2 x L. 25.00', 50, columns),
            amountLine('TOTAL DE PRUEBA', 50, columns),
            'FIN DE PRUEBA',
        ].join('\n');
        return sendTicket(printer, sample, 'Lolitas - Prueba de ticket');
    }

    function testDrawer(printer) {
        return send(printer, drawerPulse, 'Lolitas - Prueba de gaveta');
    }

    window.PosPrinter = { settings, saveSettings, printers, printSale, printOrder, testPrint, testDrawer };
})();
