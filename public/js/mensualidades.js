// JS para mensualidades: DataTable, AJAX y modales
// Este archivo debe ser incluido al final de mensualidades.php

document.addEventListener('DOMContentLoaded', function() {
    console.log('JS mensualidades.js cargado');
    
    // Alerta de confirmación antes de ejecutar el cobro mensual
    const btnCobro = document.getElementById('btnCobroMensual');
    if (btnCobro) {
        console.log('Botón #btnCobroMensual detectado');
        btnCobro.addEventListener('click', function(e) {
            console.log('Click en botón cobro mensual');
            e.preventDefault();
            if (btnCobro.disabled) return;
            
            if (confirm('¿Está seguro de que desea ejecutar el cobro mensual a todos los socios activos?')) {
                if (confirm('CONFIRME nuevamente: ¿Está completamente seguro de ejecutar el cobro mensual?')) {
                    btnCobro.disabled = true;
                    const textoOriginal = btnCobro.innerHTML;
                    btnCobro.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
                    
                    // Ejecutar cobro por AJAX
                    fetch('/corve/controllers/cobro_mensual.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Respuesta cobro mensual:', data);
                        if (data.success) {
                            // Mostrar modal con detalles del cobro
                            mostrarDetallesCobro(data);
                            const modal = new bootstrap.Modal(document.getElementById('modalCobroExito'));
                            modal.show();
                            setTimeout(() => { location.reload(); }, 2500);
                        } else {
                            alert('Error al ejecutar el cobro mensual: ' + (data.message || 'Error desconocido.'));
                            btnCobro.disabled = false;
                            btnCobro.innerHTML = textoOriginal;
                        }
                    })
                    .catch(error => {
                        alert('Error de red al ejecutar el cobro mensual.');
                        console.error('Error en fetch cobro mensual:', error);
                        btnCobro.disabled = false;
                        btnCobro.innerHTML = textoOriginal;
                    });
                }
            }
        });
    }

    // Función para mostrar detalles del cobro
    function mostrarDetallesCobro(data) {
        const mensajeElement = document.getElementById('mensajeCobroExito');
        const detallesElement = document.getElementById('detallesCobroExito');
        
        mensajeElement.innerHTML = `<strong>✓ ${data.message}</strong>`;
        
        let detallesHTML = '<div class="alert alert-info mt-3"><h6>Resumen de la ejecución:</h6><ul class="mb-0">';
        detallesHTML += '<li><strong>Socios cobrados:</strong> ' + data.cobrados + '</li>';
        detallesHTML += '<li><strong>Ya cobrados este mes:</strong> ' + data.ya_cobrados + '</li>';
        detallesHTML += '<li><strong>Primer mes (no cobran):</strong> ' + data.primer_mes + '</li>';
        detallesHTML += '<li><strong>No corresponde (no es aniversario):</strong> ' + data.no_corresponde + '</li>';
        detallesHTML += '<li><strong>Sin fecha de ingreso:</strong> ' + data.sin_fecha_ingreso + '</li>';
        detallesHTML += '<li><strong>Monto por socio:</strong> $' + (data.monto || 45000).toLocaleString('es-CO', {minimumFractionDigits: 0}) + '</li>';
        detallesHTML += '<li><strong>Fecha/Hora:</strong> ' + data.fecha + '</li>';
        detallesHTML += '</ul></div>';
        
        detallesElement.innerHTML = detallesHTML;
    }

    // Inicializar DataTables para ambas tablas
    if (window.jQuery && $.fn.DataTable) {
        // DataTable para Historial de Pagos
        $('#tablaMensualidades').DataTable({
            language: {
                url: '../../public/js/es-ES.json'
            },
            pageLength: 10,
            order: [[3, 'desc']],
            responsive: true,
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });
        
        // DataTable para Resumen por Socio
        $('#tablaResumenMensualidades').DataTable({
            language: {
                url: '../../public/js/es-ES.json'
            },
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50, 100],
            order: [[1, 'asc']],
            responsive: true
        });
    }
});
