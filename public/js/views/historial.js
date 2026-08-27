document.addEventListener('DOMContentLoaded', function () {
    const btnExportarPdf = document.getElementById('btnExportarPdf');
    if (!btnExportarPdf) return;

    btnExportarPdf.addEventListener('click', function (event) {
        event.preventDefault();

        const isAdmin = document.body.dataset.isAdmin === '1';
        const currentSucursal = this.dataset.currentBranch || '';
        const sucursales = JSON.parse(this.dataset.branches || '[]');
        const baseUrl = this.dataset.baseUrl || 'exportar_pdf.php';

        if (!isAdmin) {
            const url = new URL(baseUrl, window.location.href);
            window.open(url.toString(), '_blank');
            return;
        }

        Swal.fire({
            title: 'Selecciona la sucursal para el inventario',
            html: '<select id="sucursalPdf" class="form-select"></select>',
            confirmButtonText: 'Generar PDF',
            showCancelButton: true,
            didOpen: function () {
                const select = document.getElementById('sucursalPdf');
                select.add(new Option('Todas', ''));
                sucursales.forEach(function (sucursal) {
                    const option = new Option(sucursal.nombre_sucursal, sucursal.id_sucursal);
                    option.selected = String(sucursal.id_sucursal) === String(currentSucursal);
                    select.add(option);
                });
            },
            preConfirm: function () {
                return document.getElementById('sucursalPdf').value;
            }
        }).then(function (result) {
            if (!result.isConfirmed) return;

            const url = new URL(baseUrl, window.location.href);
            if (result.value !== '') {
                url.searchParams.set('sucursal', result.value);
            }
            const popup = window.open(url.toString(), '_blank');
            if (!popup) {
                window.location.href = url.toString();
            }
        });
    });
});
