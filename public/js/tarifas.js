// tarifas.js - Gestión de tarifas
document.addEventListener('DOMContentLoaded', function() {
    console.log('JS tarifas.js cargado');

    // Cargar tarifas al abrir la página
    cargarResumenTarifas();
    
    // Event listener para editar tarifa
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-editar')) {
            const id = e.target.closest('.btn-editar').dataset.id;
            editarTarifa(id);
        }
    });

    // Event listener para guardar cambios
    const formEditarTarifa = document.getElementById('formEditarTarifa');
    if (formEditarTarifa) {
        formEditarTarifa.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarTarifa();
        });
    }
});

/**
 * Cargar y mostrar resumen de tarifas
 */
function cargarResumenTarifas() {
    fetch('/corve/controllers/tarifa_controller.php?action=resumen')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const resumen = data.data;
                
                // Actualizar tarjetas
                actualizarMonto('monto_mensualidad', resumen['Mensualidad Activo'] || 0);
                actualizarMonto('monto_lesionado', resumen['Mensualidad Lesionado'] || 0);
                actualizarMonto('monto_afiliacion', resumen['Afiliación'] || 0);
                actualizarMonto('monto_inscripcion', resumen['Inscripción'] || 0);
                
                console.log('✓ Resumen de tarifas cargado');
            }
        })
        .catch(error => {
            console.error('Error cargando tarifas:', error);
        });
}

/**
 * Actualizar monto en tarjeta
 */
function actualizarMonto(elementId, monto) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = '$' + parseFloat(monto).toLocaleString('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }
}

/**
 * Editar una tarifa
 */
function editarTarifa(id) {
    // Buscar la fila en la tabla
    const fila = document.querySelector(`[data-id="${id}"]`).closest('tr');
    const concepto = fila.querySelector('td:nth-child(2)').textContent.trim();
    const monto = fila.querySelector('td:nth-child(3)').textContent.replace(/[$,\.]0/g, '');
    const descripcion = fila.querySelector('td:nth-child(4)').textContent.trim();
    const activa = fila.querySelector('td:nth-child(5)').textContent.includes('Activa');

    // Llenar formulario
    document.getElementById('tarifa_id').value = id;
    document.getElementById('tarifa_concepto').value = concepto;
    document.getElementById('tarifa_monto').value = monto.replace(/[$.]/g, '').trim();
    document.getElementById('tarifa_descripcion').value = descripcion;
    document.getElementById('tarifa_activa').checked = activa;

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalEditarTarifa'));
    modal.show();
}

/**
 * Guardar cambios de tarifa
 */
function guardarTarifa() {
    const id = document.getElementById('tarifa_id').value;
    const monto = document.getElementById('tarifa_monto').value;
    const descripcion = document.getElementById('tarifa_descripcion').value;
    const activa = document.getElementById('tarifa_activa').checked ? 1 : 0;

    if (!monto || monto <= 0) {
        mostrarError('El monto debe ser mayor a cero');
        return;
    }

    const datos = {
        id: parseInt(id),
        monto: parseFloat(monto),
        descripcion: descripcion,
        activa: activa
    };

    console.log('Guardando tarifa:', datos);

    fetch('/corve/controllers/tarifa_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta:', data);
        
        if (data.success) {
            // Mostrar mensaje de éxito
            const modalElement = document.getElementById('modalEditarTarifa');
            const modal = bootstrap.Modal.getInstance(modalElement);
            modal.hide();

            // Recargar la página para actualizar los valores
            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            mostrarError(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al guardar: ' + error.message);
    });
}

/**
 * Mostrar error en el modal
 */
function mostrarError(mensaje) {
    const errorDiv = document.getElementById('mensajeError');
    if (errorDiv) {
        errorDiv.textContent = mensaje;
        errorDiv.classList.remove('d-none');
    }
}

console.log('Funcionalidades de tarifas inicializadas correctamente');
