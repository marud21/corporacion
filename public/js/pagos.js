// pagos.js - JavaScript mejorado para la vista de pagos
// Incluye DataTables, modales y funcionalidades AJAX

document.addEventListener('DOMContentLoaded', function() {
    console.log('JS pagos.js cargado');

    try {
        // Actualizar saldo del socio cuando se selecciona
        const socioBuscar = document.getElementById('socio_buscar');
        if (socioBuscar) {
            socioBuscar.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const saldo = parseFloat(selectedOption.dataset.saldo) || 0;
                const saldoActual = document.getElementById('saldoActual');
                if (saldoActual) {
                    saldoActual.textContent = '$' + saldo.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            });
            console.log('✓ Event listener "socio_buscar" agregado');
        }

        // Usar el saldo como monto del pago
        const usarDeuda = document.getElementById('usarDeuda');
        if (usarDeuda) {
            usarDeuda.addEventListener('click', function() {
                const socioBuscar = document.getElementById('socio_buscar');
                if (!socioBuscar || !socioBuscar.value) {
                    alert('Por favor selecciona un socio primero');
                    return;
                }
                
                const selectedOption = socioBuscar.options[socioBuscar.selectedIndex];
                const saldo = parseFloat(selectedOption.dataset.saldo) || 0;
                
                const monto = document.getElementById('monto');
                if (monto && saldo > 0) {
                    monto.value = saldo.toFixed(2);
                    console.log('✓ Saldo usado como monto:', saldo);
                }
            });
            console.log('✓ Event listener "usarDeuda" agregado');
        }
    } catch (error) {
        console.error('Error inicializando event listeners:', error);
    }

    // Inicializar DataTables para ambas tablas
    if (window.jQuery && $.fn.DataTable) {
        // DataTable para Historial de Pagos
        $('#tablaPagos').DataTable({
            language: {
                url: '../../public/js/es-ES.json'
            },
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50, 100],
            order: [[1, 'desc']],
            responsive: true,
            columnDefs: [
                { orderable: false, targets: -1 } // Columna de acciones no ordenable
            ]
        });
        
        // DataTable para Resumen por Socio
        $('#tablaResumenPagos').DataTable({
            language: {
                url: '../../public/js/es-ES.json'
            },
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50, 100],
            order: [[1, 'asc']],
            responsive: true
        });
    } else {
        console.warn('DataTables o jQuery no están disponibles');
    }

    // Manejar click en botón de ver comprobante
    $(document).on('click', '.btn-ver-detalle', function() {
        const pagoId = $(this).data('id');
        const fila = $(this).closest('tr');
        const cols = fila.find('td');
        
        const pago = {
            id: cols.eq(0).text(),
            fecha: cols.eq(1).text(),
            socio: cols.eq(2).text(),
            documento: cols.eq(3).text(),
            concepto: cols.eq(4).text(),
            monto: cols.eq(5).text(),
            metodo_pago: cols.eq(6).text()
        };
        
        const html = `
            <div class="text-center mb-3">
                <h4 class="mb-1" style="font-weight:bold; color:#1a237e;">CORVEPATIOS</h4>
                <div style="font-size:13px; color:#666;">NIT: 900.123.456-7</div>
                <hr class="my-2" style="border-top:2px solid #1a237e;">
                <h6 style="color:#1565c0;">Comprobante de Pago</h6>
                <small>ID: <b>${pago.id}</b></small>
            </div>
            <table class="table table-bordered table-sm" style="background:#f8fafc;">
                <tr><th class="w-40">Socio</th><td>${pago.socio}</td></tr>
                <tr><th>Documento</th><td>${pago.documento}</td></tr>
                <tr><th>Fecha</th><td>${pago.fecha}</td></tr>
                <tr><th>Concepto</th><td>${pago.concepto}</td></tr>
                <tr><th>Monto</th><td><b style="color:#388e3c;">${pago.monto}</b></td></tr>
                <tr><th>Método</th><td>${pago.metodo_pago}</td></tr>
            </table>
            <div class="text-center mt-2">
                <small style="color:#888;">Generado: ${new Date().toLocaleString('es-CO')}</small>
            </div>
        `;
        
        $('#comprobantePagoBody').html(html);
        const modal = new bootstrap.Modal(document.getElementById('comprobantePagoModal'));
        modal.show();
    });

    // Imprimir comprobante
    $('#btnImprimirComprobante').on('click', function() {
        const contenido = $('#comprobantePagoBody').html();
        const ventana = window.open('', '', 'height=600,width=400');
        ventana.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Comprobante de Pago</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
                <style>
                    body { padding: 20px; font-size: 12px; }
                    table { margin-top: 15px; }
                </style>
            </head>
            <body>
                ${contenido}
            </body>
            </html>
        `);
        ventana.document.close();
        setTimeout(() => ventana.print(), 250);
    });

    // Manejar envío del formulario de nuevo pago
    const formNuevoPago = document.getElementById('formNuevoPago');
    if (formNuevoPago) {
        formNuevoPago.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Enviando formulario de pago...');
            
            try {
                // Validar que el socio esté seleccionado
                const socio_buscar = document.getElementById('socio_buscar');
                const socio_id = socio_buscar ? socio_buscar.value : null;
                
                if (!socio_id) {
                    alert('Por favor selecciona un socio');
                    return;
                }
                
                // Obtener datos del formulario
                const concepto_elem = document.getElementById('concepto');
                const monto_elem = document.getElementById('monto');
                const metodo_pago_elem = document.getElementById('metodo_pago');
                const referencia_elem = document.getElementById('referencia');
                const observaciones_elem = document.getElementById('observaciones');
                
                const concepto = concepto_elem ? concepto_elem.value : '';
                const monto = monto_elem ? parseFloat(monto_elem.value) : 0;
                const metodo_pago = metodo_pago_elem ? metodo_pago_elem.value : '';
                const referencia = referencia_elem ? (referencia_elem.value || null) : null;
                const observaciones = observaciones_elem ? (observaciones_elem.value || null) : null;
                
                const datos = {
                    socio_id: parseInt(socio_id),
                    monto: monto,
                    concepto: concepto,
                    metodo_pago: metodo_pago,
                    referencia: referencia,
                    observaciones: observaciones,
                    fecha: new Date().toISOString().slice(0, 19).replace('T', ' ')
                };
                
                console.log('Datos a enviar:', datos);
                
                // Validar datos
                if (!datos.monto || datos.monto <= 0) {
                    alert('El monto debe ser mayor a cero');
                    return;
                }
                
                if (!datos.concepto) {
                    alert('Por favor selecciona un concepto');
                    return;
                }
                
                if (!datos.metodo_pago) {
                    alert('Por favor selecciona un método de pago');
                    return;
                }
                
                const submitBtn = document.querySelector('#formNuevoPago button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
                
                fetch('/corve/controllers/pago_controller.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(datos)
                })
                .then(response => {
                    console.log('Respuesta HTTP:', response.status);
                    return response.json();
                })
                .then(response => {
                    console.log('Respuesta JSON:', response);
                    if (response.success) {
                        // Mostrar mensaje de éxito
                        const mensajeExito = document.getElementById('mensajeExito');
                        if (mensajeExito) {
                            mensajeExito.classList.remove('d-none');
                            mensajeExito.textContent = response.message || 'Pago registrado correctamente';
                        }
                        
                        // Cerrar modal
                        const modal = document.getElementById('nuevoPagoModal');
                        if (modal) {
                            const bootstrapModal = bootstrap.Modal.getInstance(modal);
                            if (bootstrapModal) bootstrapModal.hide();
                        }
                        
                        // Recargar página
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        throw new Error(response.message || 'Error desconocido');
                    }
                })
                .catch(error => {
                    console.error('Error detallado:', error);
                    const mensajeError = document.getElementById('mensajeError');
                    if (mensajeError) {
                        mensajeError.classList.remove('d-none');
                        mensajeError.textContent = error.message || 'Error al registrar el pago. Por favor intente de nuevo.';
                    }
                    
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
                
            } catch (error) {
                console.error('Error en formulario:', error);
                alert('Error: ' + error.message);
            }
        });
        console.log('✓ Event listener "formNuevoPago" agregado');
    } else {
        console.warn('⚠ Elemento "formNuevoPago" no encontrado');
    }

    // Limpiar formulario al cerrar el modal
    const nuevoPagoModal = document.getElementById('nuevoPagoModal');
    if (nuevoPagoModal) {
        nuevoPagoModal.addEventListener('hidden.bs.modal', function() {
            const formNuevoPago = document.getElementById('formNuevoPago');
            if (formNuevoPago) {
                formNuevoPago.reset();
            }
            
            const mensajeError = document.getElementById('mensajeError');
            const mensajeExito = document.getElementById('mensajeExito');
            
            if (mensajeError) mensajeError.classList.add('d-none');
            if (mensajeExito) mensajeExito.classList.add('d-none');
            
            console.log('✓ Modal limpiado');
        });
        console.log('✓ Event listener "nuevoPagoModal" agregado');
    }

    console.log('Funcionalidades de pagos inicializadas correctamente');
});
