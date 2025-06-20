<?php
// Definir la ruta base del proyecto
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));

// Incluir cabecera
include_once BASE_PATH . '/includes/header.php';

// Incluir modelos necesarios
require_once BASE_PATH . '/models/Socio.php';
require_once BASE_PATH . '/models/Pago.php';

// Configuración de la base de datos
require_once __DIR__ . '/../../includes/database/Database.php';

// Obtener conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar modelos
$socioModel = new Socio($db);
$pagoModel = new Pago($db);

// Obtener lista de socios para el select
$stmt = $socioModel->read();
$socios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener la deuda de cada socio usando el modelo Pago
foreach ($socios as &$socio) {
    $socio['deuda'] = $pagoModel->getDeudaSocio($socio['id']);
}
unset($socio);

// Obtener parámetros de filtrado
$filtros = [
    'socio_id' => $_GET['socio_id'] ?? null,
    'fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-01'), // Primer día del mes actual
    'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-t'),  // Último día del mes actual
    'concepto' => $_GET['concepto'] ?? '',
    'buscar_nombre' => $_GET['buscar_nombre'] ?? ''
];

// Obtener el historial de pagos
$pagos = $pagoModel->getHistorial($filtros);
$totalPagos = $pagoModel->getTotalRegistros($filtros);

// Calcular totales
$totalMonto = 0;
foreach ($pagos as $pago) {
    $totalMonto += (float)$pago['monto'];
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once BASE_PATH . '/includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestión de Pagos</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#nuevoPagoModal">
                        <i class="fas fa-plus"></i> Nuevo Pago
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filtrar Pagos</h5>
                </div>
                <div class="card-body">
                    <form id="filtroPagos" method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="buscar_nombre" class="form-label">Buscar por Nombre/Documento</label>
                            <input type="text" class="form-control" id="buscar_nombre" name="buscar_nombre" 
                                   value="<?php echo htmlspecialchars($_GET['buscar_nombre'] ?? ''); ?>" 
                                   placeholder="Nombre o documento del socio">
                        </div>
                        <div class="col-md-2">
                            <label for="socio_id" class="form-label">Filtrar por Socio</label>
                            <select name="socio_id" id="socio_id" class="form-select">
                                <option value="">Todos los socios</option>
                                <?php foreach ($socios as $socio): ?>
                                    <option value="<?php echo $socio['id']; ?>" <?php echo (isset($_GET['socio_id']) && $_GET['socio_id'] == $socio['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($socio['nombre'] . ' (' . $socio['documento'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="fecha_desde" class="form-label">Desde</label>
                            <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                   value="<?php echo htmlspecialchars($filtros['fecha_desde']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="fecha_hasta" class="form-label">Hasta</label>
                            <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                   value="<?php echo htmlspecialchars($filtros['fecha_hasta']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="concepto" class="form-label">Concepto</label>
                            <input type="text" class="form-control" id="concepto" name="concepto" 
                                   value="<?php echo htmlspecialchars($filtros['concepto']); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resumen -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total de Pagos</h5>
                            <p class="card-text display-6"><?php echo count($pagos); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Monto Total</h5>
                            <p class="card-text display-6">
                                $<?php echo number_format($totalMonto, 2, ',', '.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Periodo</h5>
                            <p class="card-text">
                                <?php 
                                echo date('d/m/Y', strtotime($filtros['fecha_desde'])) . ' - ' . 
                                     date('d/m/Y', strtotime($filtros['fecha_hasta'])); 
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de pagos -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Historial de Pagos</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnExportarExcel">
                            <i class="fas fa-file-excel me-1"></i> Exportar a Excel
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($pagos) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tablaPagos">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Socio</th>
                                        <th>Documento</th>
                                        <th>Concepto</th>
                                        <th class="text-end">Monto</th>
                                        <th>Método</th>
                                        <th>Referencia</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pagos as $pago): ?>
                                        <tr>
                                            <td><?php echo $pago['id']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha'])); ?></td>
                                            <td><?php echo htmlspecialchars($pago['socio_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($pago['socio_documento']); ?></td>
                                            <td><?php echo htmlspecialchars($pago['concepto']); ?></td>
                                            <td class="text-end">$<?php echo number_format($pago['monto'], 2, ',', '.'); ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($pago['metodo_pago'])); ?></td>
                                            <td><?php echo $pago['referencia'] ? htmlspecialchars($pago['referencia']) : '-'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info btn-ver-detalle" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Ver comprobante"
                                                        data-id="<?php echo $pago['id']; ?>">
                                                    <i class="fas fa-receipt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active">
                                        <th colspan="5" class="text-end">Total:</th>
                                        <th class="text-end">$<?php echo number_format($totalMonto, 2, ',', '.'); ?></th>
                                        <th colspan="3"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No se encontraron pagos con los filtros seleccionados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal para nuevo pago -->
<div class="modal fade" id="nuevoPagoModal" tabindex="-1" aria-labelledby="nuevoPagoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="nuevoPagoModalLabel">Registrar Nuevo Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formNuevoPago" action="controllers/pago_controller.php" method="POST">
                <div class="modal-body">
                    <div id="mensajeError" class="alert alert-danger d-none"></div>
                    
                    <div class="row g-3">
                        <!-- Datos del pago -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Datos del Pago</h6>
                            
                            <div class="mb-3">
                                <label for="socio_buscar" class="form-label">Buscar Socio <span class="text-danger">*</span></label>
                                <select class="form-select select2-socios" id="socio_buscar" name="socio_id" required>
                                    <option value="">Escriba para buscar un socio...</option>
                                    <?php 
                                    // Mostrar saldo y deuda correctamente para cada socio
                                    foreach ($socios as $socio): 
                                        $saldo = isset($socio['saldo']) ? (float)$socio['saldo'] : 0;
                                        $deuda = isset($socio['deuda']) ? (float)$socio['deuda'] : 0;
                                    ?>
                                        <option 
                                            value="<?php echo $socio['id']; ?>" 
                                            data-saldo="<?php echo $saldo; ?>"
                                            data-deuda="<?php echo $deuda; ?>"
                                            data-nombre="<?php echo htmlspecialchars($socio['nombre']); ?>"
                                            data-documento="<?php echo htmlspecialchars($socio['documento']); ?>">
                                            <?php 
                                                echo htmlspecialchars($socio['nombre'] . ' (' . $socio['documento'] . ') - ');
                                                if ($deuda > 0) {
                                                    echo 'Debe: $' . number_format($deuda, 2, ',', '.');
                                                } else {
                                                    echo 'Al día';
                                                }
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" id="socio_id_pago" name="socio_id">
                                <div class="row mt-2 g-2">
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body p-2 text-center">
                                                <small class="text-muted d-block">Saldo Actual</small>
                                                <h4 class="mb-0" id="saldoActual">$0.00</h4>
                                                <small class="text-muted">Disponible</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm bg-light-warning">
                                            <div class="card-body p-2 text-center">
                                                <small class="text-warning d-block">Deuda Total</small>
                                                <h4 class="mb-0 text-warning" id="deudaSocio">$0.00</h4>
                                                <small class="text-warning">Por pagar</small>
                                                <input type="hidden" id="deudaSocioValor" value="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="monto" class="form-label">Monto del Pago <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="monto" name="monto" 
                                           step="0.01" min="0.01" required
                                           placeholder="Ingrese el monto a pagar">
                                    <button class="btn btn-outline-secondary" type="button" id="usarDeuda">
                                        Usar deuda total
                                    </button>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">Máximo a pagar: <span id="montoMaximo">$0.00</span></small>
                                    <small class="text-muted">Deuda actual: <span id="montoDeuda">$0.00</span></small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="concepto" class="form-label">Concepto <span class="text-danger">*</span></label>
                                <select class="form-select" id="concepto" name="concepto" required>
                                    <option value="">Seleccione un concepto...</option>
                                    <option value="Mensualidad">Mensualidad</option>
                                    <option value="Inscripción">Inscripción</option>
                                    <option value="Afiliación">Afiliación</option>
                                    <option value="Pago deuda">Pago de deuda</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fecha" class="form-label">Fecha del Pago</label>
                                <input type="datetime-local" class="form-control" id="fecha" name="fecha" 
                                       value="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Información Adicional</h6>
                            
                            <div class="mb-3">
                                <label for="metodo_pago" class="form-label">Método de Pago</label>
                                <select class="form-select" id="metodo_pago" name="metodo_pago">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia Bancaria</option>
                                    <option value="tarjeta_credito">Tarjeta de Crédito</option>
                                    <option value="tarjeta_debito">Tarjeta de Débito</option>
                                    <option value="pse">PSE</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="referencia" class="form-label">Número de Referencia</label>
                                <input type="text" class="form-control" id="referencia" name="referencia">
                                <div class="form-text">Número de transacción, transferencia o comprobante</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i> 
                                El saldo del socio se actualizará automáticamente al registrar el pago.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnGuardarPago">
                        <i class="fas fa-save me-1"></i> Registrar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQuery y dependencias -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- CSS de Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- CSS de DataTables -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<!-- Inicialización de Select2 -->
<script>
// Esperar a que el documento esté completamente cargado
jQuery(document).ready(function($) {
    // Inicializar Select2
    $('.select2-socios').select2({
        theme: 'bootstrap-5',
        placeholder: 'Escriba para buscar un socio...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#nuevoPagoModal'),
        language: {
            noResults: function() {
                return "No se encontraron resultados";
            },
            searching: function() {
                return "Buscando...";
            },
            inputTooShort: function(args) {
                return "Ingrese al menos " + args.minimum + " caracteres";
            }
        },
        minimumInputLength: 2
    });
});
</script>

<script>
$(document).ready(function() {
    // Inicializar Select2 para la búsqueda de socios
    $('.select2-socios').select2({
        theme: 'bootstrap-5',
        placeholder: 'Escriba para buscar un socio...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#nuevoPagoModal'),
        language: {
            noResults: function() {
                return "No se encontraron resultados";
            },
            searching: function() {
                return "Buscando...";
            },
            inputTooShort: function(args) {
                return "Ingrese al menos " + args.minimum + " caracteres";
            }
        },
        minimumInputLength: 2
    });
    
    // Función para formatear moneda
    function formatearMoneda(valor, incluirSimbolo = true) {
        const valorNumerico = parseFloat(valor) || 0;
        const opciones = {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        };
        
        const formato = valorNumerico.toLocaleString('es-CO', opciones);
        return incluirSimbolo ? '$' + formato : formato;
    }
    
    // Función para convertir moneda a número
    function monedaANumero(str) {
        return parseFloat(str.replace(/[^0-9,-]+/g,"").replace(',','.')) || 0;
    }

    // Manejar cambio en la selección del socio
    function actualizarInformacionSocio() {
        const selectedOption = $('#socio_buscar').find('option:selected');
        const socioId = $('#socio_buscar').val();
        const saldo = parseFloat(selectedOption.data('saldo') || 0);
        let deuda = parseFloat(selectedOption.data('deuda') || 0);
        const nombre = selectedOption.data('nombre') || '';
        const documento = selectedOption.data('documento') || '';
        
        // Depuración: Mostrar valores en consola
        console.log('=== Depuración de Deuda ===');
        console.log('Saldo del socio:', saldo);
        console.log('Deuda inicial:', deuda);
        console.log('Data del option:', selectedOption.data());
        
        // Si el saldo es negativo, es una deuda
        if (saldo < 0) {
            deuda = Math.abs(saldo);
            console.log('Deuda calculada (abs saldo):', deuda);
        } else {
            // Si no hay deuda, mostramos 0
            deuda = 0;
            console.log('Sin deuda (saldo positivo o cero)');
        }
        
        // Actualizar el campo oculto con el ID del socio
        $('#socio_id_pago').val(socioId);
        
        // Actualizar el saldo mostrado
        const saldoFormateado = formatearMoneda(saldo);
        $('#saldoActual').html(saldo >= 0 ? saldoFormateado : '<span class="text-danger">' + saldoFormateado + '</span>');
        
        // Mostrar la deuda (usar directamente el valor de deuda calculado)
        const deudaFormateada = formatearMoneda(deuda);
        console.log('Deuda a mostrar:', deuda, 'Formateada:', deudaFormateada);
        
        $('#deudaSocio').html(deudaFormateada);
        $('#deudaSocioValor').val(deuda);
        $('#montoDeuda').text(deudaFormateada);
        
        console.log('Elementos actualizados:', {
            deudaSocio: $('#deudaSocio').html(),
            deudaSocioValor: $('#deudaSocioValor').val(),
            montoDeuda: $('#montoDeuda').text()
        });
        
        // Actualizar el monto máximo que se puede pagar (saldo positivo o 0)
        const montoMaximo = Math.max(saldo, 0);
        $('#montoMaximo').html(formatearMoneda(montoMaximo));
        
        // Establecer el máximo en el input de monto
        $('#monto')
            .attr('max', montoMaximo)
            .attr('min', '0.01')
            .val('');
            
        // Si hay deuda, sugerir ese monto como pago mínimo
        if (deuda > 0) {
            $('#monto').val(deuda.toFixed(2));
            $('#usarDeuda').removeClass('btn-outline-secondary').addClass('btn-warning');
        } else {
            $('#usarDeuda').removeClass('btn-warning').addClass('btn-outline-secondary');
        }
        
        // Actualizar el placeholder del input de monto
        $('#monto').attr('placeholder', deuda > 0 ? 
            'Mínimo recomendado: ' + formatearMoneda(deuda) : 
            'Ingrese el monto a pagar');
    }
    
    // Manejar cambio en la selección del socio
    $('#socio_buscar').on('change', actualizarInformacionSocio);
    
    // Botón para usar el monto total de la deuda
    $('#usarDeuda').on('click', function() {
        const deuda = parseFloat($('#deudaSocioValor').val()) || 0;
        if (deuda > 0) {
            $('#monto').val(deuda.toFixed(2)).trigger('input');
            $(this).addClass('btn-warning').removeClass('btn-outline-secondary');
        }
    });
    
    // Validar que el monto no sea mayor que el saldo disponible
    $('#monto').on('input', function() {
        const monto = parseFloat($(this).val()) || 0;
        const montoMaximo = parseFloat($('#monto').attr('max')) || 0;
        const deuda = parseFloat($('#deudaSocioValor').val()) || 0;
        
        // Resaltar el botón si el monto coincide con la deuda
        if (Math.abs(monto - deuda) < 0.01 && deuda > 0) {
            $('#usarDeuda').addClass('btn-warning').removeClass('btn-outline-secondary');
        } else {
            $('#usarDeuda').removeClass('btn-warning').addClass('btn-outline-secondary');
        }
        
        // Validar monto máximo
        if (monto > montoMaximo) {
            $(this).val(montoMaximo.toFixed(2));
            const montoFormateado = formatearMoneda(montoMaximo);
            alert('El monto no puede ser mayor a ' + montoFormateado);
        }
    });
    
    // Validar que el monto no sea mayor que el saldo
    $('#monto').on('input', function() {
        const monto = parseFloat($(this).val()) || 0;
        const montoMaximo = parseFloat($('#montoMaximo').text().replace(/[^0-9.-]+/g,"")) || 0;
        
        if (monto > montoMaximo) {
            $(this).val(montoMaximo.toFixed(2));
            alert('El monto no puede ser mayor a ' + formatearMoneda(montoMaximo));
        }
    });
    
    // Inicializar el modal para resetear el formulario
    $('#nuevoPagoModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('#socio_buscar').val(null).trigger('change');
        $('#saldoActual').text('$0.00');
        $('#montoMaximo').text('$0.00');
    });
    
    // Inicializar el formulario al mostrarse el modal
    $('#nuevoPagoModal').on('show.bs.modal', function () {
        // Asegurarse de que el dropdown de Select2 se muestre correctamente dentro del modal
        $('.select2-container').css('width', '100%');
        // Si hay un socio seleccionado, actualizar la información del saldo
        if ($('#socio_buscar').val()) {
            actualizarInformacionSocio();
        }
    });
    // Configuración de DataTable
    var table = $('#tablaPagos').DataTable({
        "pageLength": 50,
        "order": [[1, 'desc']],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel me-1"></i> Exportar a Excel',
                className: 'btn btn-sm btn-success',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                },
                title: 'Historial_Pagos_' + new Date().toLocaleDateString('es-CO')
            }
        ]
    });
    
    // Configurar botón de exportar
    $('#btnExportarExcel').on('click', function() {
        $('.buttons-excel').click();
    });
    
    // Actualizar información del socio seleccionado
    $('#socio_id_pago').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const saldo = parseFloat(selectedOption.data('saldo') || 0);
        
        $('#saldoActual').text('$' + saldo.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
        $('#montoMaximo').text('$' + saldo.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
        
        // Establecer el monto máximo como valor por defecto
        $('#monto').attr('max', saldo > 0 ? saldo : '');
        
        if (saldo > 0) {
            $('#monto').val(saldo.toFixed(2));
        } else {
            $('#monto').val('');
        }
    });
    
    // Validar monto máximo
    $('#monto').on('input', function() {
        const monto = parseFloat($(this).val()) || 0;
        const maximo = parseFloat($(this).attr('max')) || 0;
        
        if (monto > maximo && maximo > 0) {
            $(this).val(maximo.toFixed(2));
            Swal.fire({
                icon: 'warning',
                title: 'Monto excedido',
                text: 'El monto no puede ser mayor al saldo del socio',
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
    
    // Manejar el envío del formulario de nuevo pago
    $('#formNuevoPago').on('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const formData = new FormData(form);
        const btnSubmit = $('#btnGuardarPago');
        const btnOriginalText = btnSubmit.html();
        
        // Mostrar indicador de carga
        btnSubmit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...');
        
        // Enviar la solicitud
        fetch('controllers/pago_controller.php', {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(formData)),
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message || 'Pago registrado correctamente',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    // Cerrar el modal y recargar la página
                    $('#nuevoPagoModal').modal('hide');
                    window.location.reload();
                });
            } else {
                throw new Error(data.message || 'Error al registrar el pago');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Ocurrió un error al procesar el pago',
                confirmButtonText: 'Aceptar'
            });
        })
        .finally(() => {
            // Restaurar el botón
            btnSubmit.prop('disabled', false).html(btnOriginalText);
        });
    });
    
    // Inicializar tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Limpiar formulario al cerrar el modal
    $('#nuevoPagoModal').on('hidden.bs.modal', function () {
        document.getElementById('formNuevoPago').reset();
        $('#mensajeError').addClass('d-none').text('');
    });
});
</script>

<?php
// Incluir pie de página
include_once BASE_PATH . '/includes/footer.php';
?>
