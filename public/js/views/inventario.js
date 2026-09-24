const pastelRadio = document.getElementById('tipoPastel');
const panaderiaRadio = document.getElementById('tipoPanaderia');
const typeRadios = Array.from(document.querySelectorAll('input[name="tipo_producto"]'));
const camposPastel = document.getElementById('camposPastel');
const camposPanaderia = document.getElementById('camposPanaderia');
const camposBebida = document.getElementById('camposBebida');
const camposBatido = document.getElementById('camposBatido');
const newProductCustomizationContainer = document.getElementById('newProductCustomizationGroups');
const newProductCustomizationEditor = newProductCustomizationContainer
    ? CakeConfigurationEditor.create(newProductCustomizationContainer)
    : null;
const allowCakeDesign = document.getElementById('allowCakeDesign');
const cakeDesignPolicyFields = document.getElementById('cakeDesignPolicyFields');

function actualizarVistaTipoProducto() {
    const type = typeRadios.find((radio) => radio.checked)?.value || 'pastel';
    const sections = { pastel: camposPastel, panaderia: camposPanaderia, bebida: camposBebida, batido: camposBatido };
    Object.entries(sections).forEach(([sectionType, section]) => {
        if (!section) return;
        const active = type === sectionType;
        section.hidden = !active;
        section.style.display = active ? 'block' : 'none';
        section.querySelectorAll('input, select, textarea, button').forEach((control) => {
            control.disabled = !active;
            if (control instanceof HTMLSelectElement) control.dispatchEvent(new CustomEvent('app-select-sync'));
        });
    });
    document.querySelectorAll('[data-product-stock-input]').forEach((input) => {
        input.disabled = type === 'bebida' || type === 'batido';
        input.closest('.col-12')?.classList.toggle('opacity-50', input.disabled);
    });
}

typeRadios.forEach((radio) => radio.addEventListener('change', actualizarVistaTipoProducto));

actualizarVistaTipoProducto();

allowCakeDesign?.addEventListener('change', () => {
    cakeDesignPolicyFields?.classList.toggle('d-none', !allowCakeDesign.checked);
});

// 1. MANEJO DEL FORMULARIO DE NUEVO PRODUCTO
document.getElementById('formNuevoProducto').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.set('configuracion', JSON.stringify(pastelRadio?.checked ? newProductCustomizationEditor?.getGroups() || [] : []));

    Swal.fire({
        title: 'Procesando...',
        text: 'Guardando el nuevo producto',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

        fetch('api.php?resource=inventario&action=registrar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: '¡Logrado!',
                text: data.message,
                confirmButtonColor: '#ff85a2' // Color principal adaptado
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Hubo un problema con la conexión', 'error');
    });
});

// 2. MANEJO DEL FORMULARIO DE MERMAS (Separado y con Delegación de Eventos)
document.addEventListener('submit', function(e) {
    // Escucha cualquier envío de formulario y verifica si es de merma
    if (e.target && e.target.classList.contains('formRegistroMerma')) {
        e.preventDefault(); 
        
        const form = e.target;
        const btnSubmit = form.querySelector('button[type="submit"]');
        
        // Bloqueo visual del botón para evitar múltiples envíos
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Procesando...';
        }

        fetch('api.php?resource=inventario&action=registrarMerma', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Merma Registrada!',
                    text: data.message,
                    confirmButtonColor: '#dc3545'
                }).then(() => {
                    location.reload(); 
                });
            } else {
                Swal.fire('Error', data.message, 'error');
                // Restaurar el botón si hay error
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = 'Descontar Stock'; 
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
            // Restaurar el botón si hay error
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = 'Descontar Stock';
            }
        });
    }
});
