// JS para mensualidades: DataTable, AJAX y modales
// Este archivo debe ser incluido al final de mensualidades.php

document.addEventListener('DOMContentLoaded', function() {
    // Alerta de confirmación antes de ejecutar el cobro mensual
    const btnCobro = document.getElementById('btnCobroMensual');
    if (btnCobro) {
        btnCobro.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('¿Está seguro de que desea ejecutar el cobro mensual a todos los socios activos?')) {
                if (confirm('CONFIRME nuevamente: ¿Está completamente seguro de ejecutar el cobro mensual?')) {
                    // Ejecutar cobro por AJAX y mostrar modal de éxito
                    fetch('../../cobro_mensual.php')
                        .then(response => response.text())
                        .then(data => {
                            // Mostrar modal de éxito
                            var modal = new bootstrap.Modal(document.getElementById('modalCobroExito'));
                            modal.show();
                        })
                        .catch(() => {
                            alert('Ocurrió un error al ejecutar el cobro mensual.');
                        });
                }
            }
        });
    }

    if (window.jQuery && $.fn.DataTable) {
        $('#tablaMensualidades').DataTable({
            language: {
                url: '/corve/public/js/es-ES.json'
            },
            pageLength: 10,
            order: [[4, 'desc']],
            responsive: true
        });
    }
});
