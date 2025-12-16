<?php
// pagos.php - Vista mejorada de gestión de pagos
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
$socioModel = new Socio();
$pagoModel = new Pago($db);

// Obtener lista de socios
$stmtSocios = $db->prepare("
    SELECT s.id, s.saldo, s.estado, u.nombre, u.documento 
    FROM socios s 
    LEFT JOIN usuarios u ON s.id = u.id 
    ORDER BY u.nombre ASC
");
$stmtSocios->execute();
$socios = $stmtSocios->fetchAll(PDO::FETCH_ASSOC);

// Obtener parámetros de filtrado
$filtros = [
    'socio_id' => $_GET['socio_id'] ?? null,
    'fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-01'),
    'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-t'),
    'concepto' => $_GET['concepto'] ?? '',
    'buscar_nombre' => $_GET['buscar_nombre'] ?? ''
];

// Obtener el historial de pagos
$pagos = $pagoModel->getHistorial($filtros);

// Calcular totales
$totalPagos = count($pagos);
$totalMonto = 0;
foreach ($pagos as $pago) {
    $totalMonto += (float)$pago['monto'];
}

// Crear array con resumen por socio
$resumenPorSocio = [];
foreach ($socios as $socio) {
    $socioId = $socio['id'];
    $pagosSocio = array_filter($pagos, function($p) use ($socioId) {
        return $p['socio_id'] == $socioId;
    });
    
    $totalPagadoSocio = 0;
    foreach ($pagosSocio as $pago) {
        $totalPagadoSocio += (float)$pago['monto'];
    }
    
    $resumenPorSocio[$socioId] = [
        'nombre' => $socio['nombre'],
        'documento' => $socio['documento'],
        'estado' => $socio['estado'],
        'saldo' => $socio['saldo'],
        'total_pagado' => $totalPagadoSocio,
        'cantidad_pagos' => count($pagosSocio)
    ];
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
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#nuevoPagoModal">
                    <i class="fas fa-plus"></i> Registrar Pago
                </button>
            </div>

            <!-- Resumen General -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Pagado</h5>
                            <p class="card-text display-6">$<?= number_format($totalMonto, 0, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total de Transacciones</h5>
                            <p class="card-text display-6"><?= $totalPagos ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Filtrar Pagos</h5>
                </div>
                <div class="card-body">
                    <form id="filtroPagos" method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="buscar_nombre" class="form-label">Buscar Socio</label>
                            <input type="text" class="form-control" id="buscar_nombre" name="buscar_nombre" 
                                   value="<?php echo htmlspecialchars($_GET['buscar_nombre'] ?? ''); ?>" 
                                   placeholder="Nombre o documento">
                        </div>
                        <div class="col-md-2">
                            <label for="socio_id" class="form-label">Socio</label>
                            <select name="socio_id" id="socio_id" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach ($socios as $socio): ?>
                                    <option value="<?php echo $socio['id']; ?>" <?php echo (isset($_GET['socio_id']) && $_GET['socio_id'] == $socio['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($socio['nombre']); ?>
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
                        <div class="col-md-2">
                            <label for="concepto_filtro" class="form-label">Concepto</label>
                            <select class="form-select" id="concepto_filtro" name="concepto">
                                <option value="">Todos</option>
                                <option value="Mensualidad" <?php if($filtros['concepto']==='Mensualidad') echo 'selected'; ?>>Mensualidad</option>
                                <option value="Inscripción" <?php if($filtros['concepto']==='Inscripción') echo 'selected'; ?>>Inscripción</option>
                                <option value="Afiliación" <?php if($filtros['concepto']==='Afiliación') echo 'selected'; ?>>Afiliación</option>
                                <option value="Pago deuda" <?php if($filtros['concepto']==='Pago deuda') echo 'selected'; ?>>Pago deuda</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de pagos con DataTables -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Historial de Pagos</h5>
                </div>
                <div class="card-body">
                    <?php if (count($pagos) > 0): ?>
                        <div class="table-responsive">
                            <table id="tablaPagos" class="table table-striped table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Socio</th>
                                        <th>Documento</th>
                                        <th>Concepto</th>
                                        <th class="text-end">Monto</th>
                                        <th>Método</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pagos as $pago): ?>
                                        <tr>
                                            <td><?php echo $pago['id']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha'])); ?></td>
                                            <td><strong><?php echo htmlspecialchars($pago['socio_nombre']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($pago['socio_documento']); ?></td>
                                            <td><?php echo htmlspecialchars($pago['concepto']); ?></td>
                                            <td class="text-end text-success fw-bold">$<?php echo number_format($pago['monto'], 0, ',', '.'); ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($pago['metodo_pago'] ?? 'N/A')); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info btn-ver-detalle" 
                                                        title="Ver comprobante"
                                                        data-id="<?php echo $pago['id']; ?>">
                                                    <i class="fas fa-receipt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active fw-bold">
                                        <th colspan="5" class="text-end">Total:</th>
                                        <th class="text-end">$<?php echo number_format($totalMonto, 0, ',', '.'); ?></th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> No se encontraron pagos con los filtros seleccionados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Resumen por Socio -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Resumen de Pagos por Socio</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaResumenPagos" class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Documento</th>
                                    <th>Estado</th>
                                    <th class="text-center">Pagos Realizados</th>
                                    <th class="text-end">Total Pagado</th>
                                    <th class="text-end">Saldo Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resumenPorSocio as $socioId => $resumen): 
                                    $saldoClass = ($resumen['saldo'] > 0) ? 'text-danger fw-bold' : 'text-success fw-bold';
                                    $estadoClass = ($resumen['estado'] === 'activo') ? 'badge bg-success' : ($resumen['estado'] === 'lesionado' ? 'badge bg-warning text-dark' : 'badge bg-secondary');
                                ?>
                                <tr>
                                    <td><?php echo $socioId; ?></td>
                                    <td><?php echo htmlspecialchars($resumen['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($resumen['documento']); ?></td>
                                    <td><span class="<?php echo $estadoClass; ?>"><?php echo ucfirst($resumen['estado']); ?></span></td>
                                    <td class="text-center"><span class="badge bg-primary"><?php echo $resumen['cantidad_pagos']; ?></span></td>
                                    <td class="text-end text-success fw-bold">$<?php echo number_format($resumen['total_pagado'], 0, ',', '.'); ?></td>
                                    <td class="<?php echo $saldoClass; ?>">$<?php echo number_format($resumen['saldo'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal para nuevo pago (código original simplificado) -->
<div class="modal fade" id="nuevoPagoModal" tabindex="-1" aria-labelledby="nuevoPagoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="nuevoPagoModalLabel"><i class="fas fa-money-bill-wave"></i> Registrar Nuevo Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formNuevoPago" action="controllers/pago_controller.php" method="POST">
                <div class="modal-body">
                    <div id="mensajeError" class="alert alert-danger d-none"></div>
                    <div id="mensajeExito" class="alert alert-success d-none"></div>
                    
                    <div class="row g-3">
                        <!-- Datos del pago -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Datos del Pago</h6>
                            
                            <div class="mb-3">
                                <label for="socio_buscar" class="form-label">Socio <span class="text-danger">*</span></label>
                                <select class="form-select" id="socio_buscar" name="socio_id" required>
                                    <option value="">Seleccione un socio...</option>
                                    <?php 
                                    foreach ($socios as $socio): 
                                        $saldo = isset($socio['saldo']) ? (float)$socio['saldo'] : 0;
                                    ?>
                                        <option 
                                            value="<?php echo $socio['id']; ?>" 
                                            data-saldo="<?php echo $saldo; ?>"
                                            data-nombre="<?php echo htmlspecialchars($socio['nombre']); ?>">
                                            <?php echo htmlspecialchars($socio['nombre'] . ' (' . $socio['documento'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3 p-3 bg-light rounded">
                                <small class="text-danger d-block mb-1">Saldo Pendiente</small>
                                <h4 class="mb-0" id="saldoActual">$0.00</h4>
                            </div>
                            
                            <div class="mb-3">
                                <label for="monto" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="monto" name="monto" 
                                           step="0.01" min="0.01" required placeholder="0.00">
                                    <button class="btn btn-outline-secondary" type="button" id="usarDeuda">
                                        Usar Saldo
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="concepto" class="form-label">Concepto <span class="text-danger">*</span></label>
                                <select class="form-select" id="concepto" name="concepto" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Mensualidad">Mensualidad</option>
                                    <option value="Inscripción">Inscripción</option>
                                    <option value="Afiliación">Afiliación</option>
                                    <option value="Pago deuda">Pago de deuda</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Datos adicionales -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Datos Adicionales</h6>
                            
                            <div class="mb-3">
                                <label for="metodo_pago" class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                    <option value="">Seleccione...</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="tarjeta">Tarjeta</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="referencia" class="form-label">Referencia</label>
                                <input type="text" class="form-control" id="referencia" name="referencia" 
                                       placeholder="Número de transacción, cheque, etc.">
                            </div>

                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" 
                                          rows="3" placeholder="Notas adicionales"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Registrar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para comprobante de pago -->
<div class="modal fade" id="comprobantePagoModal" tabindex="-1" aria-labelledby="comprobantePagoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="comprobantePagoLabel">Comprobante de Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="comprobantePagoBody">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="btnImprimirComprobante" class="btn btn-primary">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../../public/js/pagos.js?v=<?= time(); ?>"></script>

<?php include_once BASE_PATH . '/includes/footer.php'; ?>
