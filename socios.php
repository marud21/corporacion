<?php
// socios.php - Vista mejorada de gestión de socios
// Siguiendo el patrón de mensualidades y pagos

define('BASE_PATH', dirname(__FILE__));

include_once BASE_PATH . '/includes/header.php';

require_once BASE_PATH . '/models/Socio.php';
require_once BASE_PATH . '/models/Tarifa.php';
require_once BASE_PATH . '/includes/database/Database.php';

// Obtener conexión a BD
$database = new Database();
$db = $database->getConnection();

// Inicializar modelos
$socioModel = new Socio();
$tarifaModel = new Tarifa($db);

// Obtener lista de socios
try {
    // Obtener todos los socios activos (en BD) con todos los estados (activo, lesionado, retirado)
    $stmt = $socioModel->read(false); // false = no filtrar por estado, solo por activo=1 en BD
    $socios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $socios = [];
    $error = "Error al cargar socios: " . $e->getMessage();
}

// Calcular estadísticas
$totalSocios = count($socios);
$activos = array_filter($socios, function($s) { return ($s['estado'] ?? '') === 'activo'; });
$lesionados = array_filter($socios, function($s) { return ($s['estado'] ?? '') === 'lesionado'; });
$retirados = array_filter($socios, function($s) { return ($s['estado'] ?? '') === 'retirado'; });

$saldoTotal = 0;
$deudaTotal = 0;
foreach ($socios as $socio) {
    $saldo = (float)($socio['saldo'] ?? 0);
    $saldoTotal += $saldo;
    if ($saldo > 0) $deudaTotal += $saldo;
}

// Obtener tarifas
$tarifas = $tarifaModel->getResumen();
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once BASE_PATH . '/includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-users"></i> Gestión de Socios</h1>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#nuevoSocioModal">
                    <i class="fas fa-plus"></i> Nuevo Socio
                </button>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Resumen General -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Socios</h5>
                            <p class="card-text display-6"><?php echo $totalSocios; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Activos</h5>
                            <p class="card-text display-6"><?php echo count($activos); ?></p>
                            <small>Pagando mensualidad completa</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Lesionados</h5>
                            <p class="card-text display-6"><?php echo count($lesionados); ?></p>
                            <small>Pagando tarifa reducida</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">Deuda Total</h5>
                            <p class="card-text display-6">$<?php echo number_format($deudaTotal, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-filter"></i> Filtros</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="buscar_socio" class="form-label">Buscar por Nombre/Documento</label>
                            <input type="text" class="form-control" id="buscar_socio" placeholder="Juan Pérez o 1234567890">
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_estado" class="form-label">Estado</label>
                            <select class="form-select" id="filtro_estado">
                                <option value="">Todos los estados</option>
                                <option value="activo">Activo</option>
                                <option value="lesionado">Lesionado</option>
                                <option value="retirado">Retirado</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100" id="btn_filtrar">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Socios -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Listado de Socios</h5>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0" id="tablaSocios">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">ID</th>
                                <th style="width: 20%">Nombre</th>
                                <th style="width: 15%">Documento</th>
                                <th style="width: 15%">Teléfono</th>
                                <th style="width: 15%">Estado</th>
                                <th style="width: 15%">Saldo/Deuda</th>
                                <th style="width: 5%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($socios as $socio): 
                                $saldo = (float)($socio['saldo'] ?? 0);
                                $estado = $socio['estado'] ?? 'activo';
                                
                                // Color del estado
                                $estadoColor = 'secondary';
                                if ($estado === 'activo') $estadoColor = 'success';
                                elseif ($estado === 'lesionado') $estadoColor = 'warning';
                                elseif ($estado === 'retirado') $estadoColor = 'danger';
                                
                                // Color del saldo
                                $saldoColor = 'text-success';
                                if ($saldo > 0) $saldoColor = 'text-danger fw-bold';
                            ?>
                            <tr>
                                <td><small><?php echo $socio['id']; ?></small></td>
                                <td><strong><?php echo htmlspecialchars($socio['nombre'] ?? 'N/A'); ?></strong></td>
                                <td><small><?php echo htmlspecialchars($socio['documento'] ?? 'N/A'); ?></small></td>
                                <td><small><?php echo htmlspecialchars($socio['telefono'] ?? 'N/A'); ?></small></td>
                                <td>
                                    <span class="badge bg-<?php echo $estadoColor; ?>">
                                        <?php echo ucfirst($estado); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $saldoColor; ?>">
                                        <?php 
                                        if ($saldo > 0) {
                                            echo "Debe: $" . number_format($saldo, 0, ',', '.');
                                        } else {
                                            echo "Al día";
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info btn-ver" data-id="<?php echo $socio['id']; ?>" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning btn-editar" data-id="<?php echo $socio['id']; ?>" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?php echo $socio['id']; ?>" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Información -->
            <div class="alert alert-info mt-3" role="alert">
                <i class="fas fa-info-circle"></i>
                <strong>Información:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>Activos:</strong> Socios pagando mensualidad completa ($<?php echo number_format($tarifas['Mensualidad Activo'] ?? 45000, 0, ',', '.'); ?>)</li>
                    <li><strong>Lesionados:</strong> Socios con tarifa reducida ($<?php echo number_format($tarifas['Mensualidad Lesionado'] ?? 10000, 0, ',', '.'); ?>)</li>
                    <li><strong>Retirados:</strong> Socios sin cobro hasta reactivarse</li>
                </ul>
            </div>
        </main>
    </div>
</div>

<!-- Modal para nuevo socio -->
<div class="modal fade" id="nuevoSocioModal" tabindex="-1" aria-labelledby="nuevoSocioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="nuevoSocioModalLabel">
                    <i class="fas fa-user-plus"></i> Nuevo Socio
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formNuevoSocio" action="controllers/socio_controller.php" method="POST">
                <div class="modal-body">
                    <div id="mensajeError" class="alert alert-danger d-none"></div>
                    <div id="mensajeExito" class="alert alert-success d-none"></div>
                    
                    <div class="row g-3">
                        <!-- Columna 1 -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Datos Personales</h6>
                            
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="documento" class="form-label">Documento <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="documento" name="documento" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" required>
                            </div>
                        </div>
                        
                        <!-- Columna 2 -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Datos de Afiliación</h6>
                            
                            <div class="mb-3">
                                <label for="fecha_afiliacion" class="form-label">Fecha de Afiliación <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_afiliacion" name="fecha_afiliacion" required value="<?php echo date('Y-m-d'); ?>">
                                <small class="text-muted">Importante: Determina el día de cobro mensual</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-select" id="estado" name="estado" required>
                                    <option value="activo" selected>Activo</option>
                                    <option value="lesionado">Lesionado</option>
                                    <option value="retirado">Retirado</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion">
                            </div>
                            
                            <div class="mb-3">
                                <label for="entidad_salud" class="form-label">Entidad de Salud</label>
                                <input type="text" class="form-control" id="entidad_salud" name="entidad_salud">
                            </div>
                            
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="foto_documento" class="form-label">Foto de Documento <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="foto_documento" name="foto_documento" accept="image/*" required>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle"></i> Formato: JPG, PNG, GIF. Máximo 5MB. Es obligatorio.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Crear Socio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar socio -->
<div class="modal fade" id="editarSocioModal" tabindex="-1" aria-labelledby="editarSocioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="editarSocioModalLabel">
                    <i class="fas fa-user-edit"></i> Editar Socio
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEditarSocio" action="controllers/socio_controller.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div id="mensajeErrorEditar" class="alert alert-danger d-none"></div>
                    
                    <input type="hidden" id="socio_id" name="id">
                    <input type="hidden" name="action" value="update">
                    
                    <!-- Sección de Foto -->
                    <div class="alert alert-info mb-3">
                        <h6 class="alert-heading"><i class="fas fa-camera"></i> Foto del Documento de Identidad</h6>
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <img id="editFotoPreview" src="" alt="Foto actual" class="img-fluid rounded" style="max-height: 200px; display: none;">
                                        <small id="editSinFoto" class="text-muted" style="display: none;">No hay foto</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="edit_foto_documento" class="form-label">Cambiar Foto <small class="text-muted">(opcional)</small></label>
                                    <input type="file" class="form-control" id="edit_foto_documento" name="foto_documento" accept="image/*">
                                    <small class="text-muted d-block mt-2">
                                        ✅ Formatos: JPG, PNG, GIF<br>
                                        ✅ Máximo: 5 MB<br>
                                        ℹ️ Dejar en blanco mantiene la foto actual
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <!-- Columna 1 -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Datos Personales</h6>
                            
                            <div class="mb-3">
                                <label for="edit_nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_documento" class="form-label">Documento <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_documento" name="documento" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="edit_fecha_nacimiento" name="fecha_nacimiento">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="edit_telefono" name="telefono" required>
                            </div>
                        </div>
                        
                        <!-- Columna 2 -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Datos de Afiliación</h6>
                            
                            <div class="mb-3">
                                <label for="edit_fecha_afiliacion" class="form-label">Fecha de Afiliación <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_fecha_afiliacion" name="fecha_afiliacion" required>
                                <small class="text-muted">Determina el día de cobro mensual</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_estado" name="estado" required>
                                    <option value="activo">Activo</option>
                                    <option value="lesionado">Lesionado</option>
                                    <option value="retirado">Retirado</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="edit_direccion" name="direccion">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_entidad_salud" class="form-label">Entidad de Salud</label>
                                <input type="text" class="form-control" id="edit_entidad_salud" name="entidad_salud">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="edit_observaciones" name="observaciones" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarCambios" class="btn btn-warning">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del socio -->
<div class="modal fade" id="verSocioModal" tabindex="-1" aria-labelledby="verSocioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="verSocioModalLabel">
                    <i class="fas fa-user-circle"></i> Detalles del Socio
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Foto de documento -->
                    <div class="col-md-4 text-center">
                        <div class="card">
                            <div class="card-body">
                                <div id="fotoDocumentoContainer" class="mb-3">
                                    <img id="fotoDocumento" src="" alt="Documento" class="img-fluid rounded" style="max-height: 300px;">
                                </div>
                                <small id="sinFoto" class="text-muted d-none">No hay foto del documento</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información personal -->
                    <div class="col-md-8">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-user"></i> Información Personal
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><small class="text-muted">Nombre</small></label>
                                <p id="verNombre" class="fw-bold">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><small class="text-muted">Documento</small></label>
                                <p id="verDocumento" class="fw-bold">-</p>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><small class="text-muted">Email</small></label>
                                <p id="verEmail">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><small class="text-muted">Teléfono</small></label>
                                <p id="verTelefono">-</p>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><small class="text-muted">Fecha de Nacimiento</small></label>
                                <p id="verFechaNacimiento">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><small class="text-muted">Dirección</small></label>
                                <p id="verDireccion">-</p>
                            </div>
                        </div>
                        
                        <!-- Información de afiliación -->
                        <h6 class="border-bottom pb-2 mb-3 mt-4">
                            <i class="fas fa-file-alt"></i> Información de Afiliación
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><small class="text-muted">Fecha de Afiliación</small></label>
                                <p id="verFechaAfiliacion" class="fw-bold">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><small class="text-muted">Estado</small></label>
                                <p id="verEstado">-</p>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><small class="text-muted">Entidad de Salud</small></label>
                                <p id="verEntidadSalud">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><small class="text-muted">Saldo/Deuda</small></label>
                                <p id="verSaldo" class="fw-bold">-</p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><small class="text-muted">Observaciones</small></label>
                            <p id="verObservaciones">-</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning" id="btnEditarDesdeVer" data-bs-dismiss="modal">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/corve/public/js/socios.js?v=<?= time(); ?>"></script>

<?php include_once BASE_PATH . '/includes/footer.php'; ?>
