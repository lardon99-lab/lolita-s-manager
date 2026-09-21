document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-mermar-caducado').forEach((button) => {
        button.addEventListener('click', async function (event) {
            event.preventDefault();
            const confirmation = await Swal.fire({
                title: '¿Retirar lote caducado?',
                text: `Se registrará todo el stock de "${this.dataset.nombre}" como merma por caducidad.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrar merma',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
            });
            if (!confirmation.isConfirmed) return;

            Swal.fire({
                title: 'Procesando...',
                text: 'Actualizando el inventario.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });
            const body = new FormData();
            body.append('action', 'registrar_merma');
            body.append('id_inventario', this.dataset.id);

            try {
                const response = await fetch('api.php?resource=inventario', {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body,
                });
                const data = await response.json();
                if (!response.ok || data.status !== 'success') throw new Error(data.message || 'No fue posible registrar la merma.');
                await Swal.fire({ icon: 'success', title: 'Lote retirado', text: data.message });
                location.reload();
            } catch (error) {
                await Swal.fire({ icon: 'error', title: 'Operación fallida', text: error.message });
            }
        });
    });
});
