<?php
// Definir la ruta base del proyecto
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));

// Incluir cabecera
include_once BASE_PATH . '/includes/header.php';

// Incluir el modelo Socio
require_once BASE_PATH . '/models/Socio.php';

// Crear instancia del modelo
$socio = new Socio();

// Obtener todos los socios con sus saldos
$stmt = $socio->read();
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once BASE_PATH . '/includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Saldos y Deudas de Socios</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnExportar">
                            <i class="fas fa-file-export me-1"></i> Exportar a Excel
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaSaldos">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Documento</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th class="text-end">Saldo</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                    $saldo = (float)$row['saldo'];
                                    $claseSaldo = $saldo < 0 ? 'text-danger' : 'text-success';
                                    $simboloSaldo = $saldo < 0 ? '-' : '';
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['documento']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                        <td class="text-end <?php echo $claseSaldo; ?> fw-bold">
                                            <?php echo $simboloSaldo . '$' . number_format(abs($saldo), 2, ',', '.'); ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary btn-ver-detalle" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detalleSocioModal"
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                                    data-saldo="<?php echo $saldo; ?>">
                                                <i class="fas fa-eye"></i> Ver Detalle
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal para ver el detalle del socio -->
<div class="modal fade" id="detalleSocioModal" tabindex="-1" aria-labelledby="detalleSocioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detalleSocioModalLabel">Detalle de Saldo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 id="nombreSocio"></h5>
                        <p class="mb-1">ID: <span id="idSocio"></span></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <h4 class="mb-0">Saldo Actual:</h4>
                        <h2 id="saldoActual" class="mb-0"></h2>
                    </div>
                </div>

                <h6 class="border-bottom pb-2 mb-3">Últimos Movimientos</h6>
                <div class="table-responsive">
                    <table class="table table-sm" id="tablaMovimientos">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th>Monto</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoMovimientos">
                            <tr>
                                <td colspan="4" class="text-center">Cargando movimientos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnNuevoPago">
                    <i class="fas fa-plus-circle me-1"></i> Registrar Pago
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para registrar pago -->
<div class="modal fade" id="registrarPagoModal" tabindex="-1" aria-labelledby="registrarPagoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="registrarPagoModalLabel">Registrar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formRegistrarPago">
                <div class="modal-body">
                    <input type="hidden" id="socioIdPago" name="socio_id">
                    
                    <div class="mb-3">
                        <label for="montoPago" class="form-label">Monto del Pago</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="montoPago" name="monto" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="conceptoPago" class="form-label">Concepto</label>
                        <select class="form-select" id="conceptoPago" name="concepto" required>
                            <option value="">Seleccione un concepto</option>
                            <option value="mensualidad">Mensualidad</option>
                            <option value="afiliacion">Afiliación</option>
                            <option value="inscripcion">Inscripción</option>
                            <option value="sancion">Sanción</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fechaPago" class="form-label">Fecha del Pago</label>
                        <input type="date" class="form-control" id="fechaPago" name="fecha" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comentarioPago" class="form-label">Comentario (Opcional)</label>
                        <textarea class="form-control" id="comentarioPago" name="comentario" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Guardar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Incluir DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">

<!-- Incluir DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    var table = $('#tablaSaldos').DataTable({
        "pageLength": 25,
        "order": [[1, 'asc']],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel me-1"></i> Exportar a Excel',
                className: 'btn btn-sm btn-outline-secondary',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }
        ]
    });

    // Manejar clic en el botón de exportar
    $('#btnExportar').on('click', function() {
        $('.buttons-excel').click();
    });

    // Manejar la apertura del modal de detalle
    var detalleModal = document.getElementById('detalleSocioModal');
    detalleModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var idSocio = button.getAttribute('data-id');
        var nombreSocio = button.getAttribute('data-nombre');
        var saldoSocio = parseFloat(button.getAttribute('data-saldo'));
        
        // Actualizar la información en el modal
        document.getElementById('nombreSocio').textContent = nombreSocio;
        document.getElementById('idSocio').textContent = idSocio;
        
        var saldoElement = document.getElementById('saldoActual');
        saldoElement.textContent = (saldoSocio < 0 ? '-' : '') + '$' + Math.abs(saldoSocio).toFixed(2).replace(/\./g, ',');
        saldoElement.className = saldoSocio < 0 ? 'text-danger' : 'text-success';
        
        // Guardar el ID del socio en un campo oculto para usarlo en el formulario de pago
        document.getElementById('socioIdPago').value = idSocio;
        
        // Cargar los movimientos del socio
        cargarMovimientosSocio(idSocio);
    });
    
    // Función para cargar los movimientos del socio
    function cargarMovimientosSocio(idSocio) {
        // Aquí iría la llamada AJAX para obtener los movimientos del socio
        // Por ahora, mostramos un mensaje de carga
        $('#cuerpoMovimientos').html('<tr><td colspan="4" class="text-center">Cargando movimientos...</td></tr>');
        
        // Simulación de carga de datos (reemplazar con llamada AJAX real)
        setTimeout(function() {
            // Datos de ejemplo (eliminar en producción)
            var movimientos = [
                { fecha: '2023-06-07', concepto: 'Mensualidad Junio 2023', monto: 45.00, saldo: -45.00 },
                { fecha: '2023-05-07', concepto: 'Mensualidad Mayo 2023', monto: 45.00, saldo: -90.00 },
                { fecha: '2023-05-01', concepto: 'Pago recibido', monto: 100.00, saldo: 10.00 },
                { fecha: '2023-04-07', concepto: 'Mensualidad Abril 2023', monto: 45.00, saldo: -90.00 },
                { fecha: '2023-03-07', concepto: 'Mensualidad Marzo 2023', monto: 45.00, saldo: -45.00 }
            ];
            
            var html = '';
            movimientos.forEach(function(mov) {
                var claseMonto = mov.monto < 0 ? 'text-danger' : 'text-success';
                var claseSaldo = mov.saldo < 0 ? 'text-danger' : 'text-success';
                var simboloMonto = mov.monto < 0 ? '-' : '';
                var simboloSaldo = mov.saldo < 0 ? '-' : '';
                
                html += '<tr>';
                html += '<td>' + mov.fecha + '</td>';
                html += '<td>' + mov.concepto + '</td>';
                html += '<td class="' + claseMonto + ' text-end">' + simboloMonto + '$' + Math.abs(mov.monto).toFixed(2).replace(/\./g, ',') + '</td>';
                html += '<td class="' + claseSaldo + ' text-end fw-bold">' + simboloSaldo + '$' + Math.abs(mov.saldo).toFixed(2).replace(/\./g, ',') + '</td>';
                html += '</tr>';
            });
            
            $('#cuerpoMovimientos').html(html);
        }, 800);
    }
    
    // Manejar clic en el botón de nuevo pago
    $('#btnNuevoPago').on('click', function() {
        // Cerrar el modal actual
        var modal = bootstrap.Modal.getInstance(document.getElementById('detalleSocioModal'));
        modal.hide();
        
        // Mostrar el modal de registro de pago
        var pagoModal = new bootstrap.Modal(document.getElementById('registrarPagoModal'));
        pagoModal.show();
    });
    
    // Establecer la fecha actual por defecto en el formulario de pago
    document.getElementById('fechaPago').valueAsDate = new Date();
    
    // Manejar el envío del formulario de pago
    $('#formRegistrarPago').on('submit', function(e) {
        e.preventDefault();
        
        // Aquí iría el código para enviar el pago al servidor
        // Por ahora, mostramos un mensaje de éxito
        alert('Pago registrado exitosamente');
        
        // Cerrar el modal de registro de pago
        var pagoModal = bootstrap.Modal.getInstance(document.getElementById('registrarPagoModal'));
        pagoModal.hide();
        
        // Actualizar la tabla de movimientos
        var idSocio = $('#socioIdPago').val();
        cargarMovimientosSocio(idSocio);
    });
});
</script>

<?php
// Incluir pie de página
include_once BASE_PATH . '/includes/footer.php';
?>
