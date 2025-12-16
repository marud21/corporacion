<?php
// views/admin/tarifas.php - Gestión de tarifas del sistema
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));

include_once BASE_PATH . '/includes/header.php';

require_once BASE_PATH . '/models/Tarifa.php';
require_once BASE_PATH . '/includes/database/Database.php';

$database = new Database();
$db = $database->getConnection();
$tarifaModel = new Tarifa($db);

// Obtener todas las tarifas
$tarifas = $tarifaModel->getAll();
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once BASE_PATH . '/includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-dollar-sign"></i> Configuración de Tarifas</h1>
                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                    <i class="fas fa-question-circle"></i> Ayuda
                </button>
            </div>

            <!-- Resumen de tarifas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-1">Mensualidad (Activo)</h6>
                            <p class="card-text display-6" id="monto_mensualidad">$0</p>
                            <small>Cobro mensual completo</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <h6 class="card-title mb-1">Mensualidad (Lesionado)</h6>
                            <p class="card-text display-6" id="monto_lesionado">$0</p>
                            <small>Cobro reducido por lesión</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-1">Afiliación</h6>
                            <p class="card-text display-6" id="monto_afiliacion">$0</p>
                            <small>Cuota inicial de ingreso</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-1">Inscripción</h6>
                            <p class="card-text display-6" id="monto_inscripcion">$0</p>
                            <small>Tarifa administrativa</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de tarifas -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Tarifas del Sistema</h5>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0" id="tablaTarifas">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">ID</th>
                                <th style="width: 30%">Concepto</th>
                                <th style="width: 20%">Monto</th>
                                <th style="width: 30%">Descripción</th>
                                <th style="width: 10%">Estado</th>
                                <th style="width: 5%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tarifas as $tarifa): ?>
                            <tr>
                                <td><?php echo $tarifa['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($tarifa['concepto']); ?></strong></td>
                                <td>
                                    <span class="badge bg-success">
                                        $<?php echo number_format((float)$tarifa['monto'], 0, ',', '.'); ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.9em; color: #666;">
                                    <?php echo htmlspecialchars($tarifa['descripcion'] ?? ''); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $tarifa['activa'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $tarifa['activa'] ? 'Activa' : 'Inactiva'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-editar" data-id="<?php echo $tarifa['id']; ?>" title="Editar">
                                        <i class="fas fa-edit"></i>
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
                <strong>Nota:</strong> Los montos configurados aquí se utilizarán automáticamente en:
                <ul class="mb-0 mt-2">
                    <li>Cobro mensual de mensualidades</li>
                    <li>Registro de nuevos socios</li>
                    <li>Generación de comprobantes</li>
                </ul>
            </div>
        </main>
    </div>
</div>

<!-- Modal para editar tarifa -->
<div class="modal fade" id="modalEditarTarifa" tabindex="-1" aria-labelledby="modalEditarTarifaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalEditarTarifaLabel">
                    <i class="fas fa-edit"></i> Editar Tarifa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEditarTarifa">
                <div class="modal-body">
                    <input type="hidden" id="tarifa_id" name="id">
                    
                    <div class="mb-3">
                        <label for="tarifa_concepto" class="form-label">Concepto</label>
                        <input type="text" class="form-control" id="tarifa_concepto" disabled>
                        <small class="text-muted">No se puede cambiar el concepto</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tarifa_monto" class="form-label">Monto <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="tarifa_monto" name="monto" 
                                   step="1000" min="0" required placeholder="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tarifa_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="tarifa_descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="tarifa_activa" name="activa" value="1" checked>
                        <label class="form-check-label" for="tarifa_activa">
                            Tarifa activa
                        </label>
                    </div>
                    
                    <div id="mensajeError" class="alert alert-danger d-none mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de ayuda -->
<div class="modal fade" id="modalAyuda" tabindex="-1" aria-labelledby="modalAyudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAyudaLabel">
                    <i class="fas fa-question-circle"></i> Gestión de Tarifas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <h6>¿Qué son las tarifas?</h6>
                <p>Las tarifas son los montos que se cobran a los socios por diferentes conceptos:</p>
                
                <table class="table table-bordered">
                    <tr>
                        <th>Concepto</th>
                        <th>Uso</th>
                    </tr>
                    <tr>
                        <td><strong>Mensualidad Activo</strong></td>
                        <td>Cobro mensual a socios en estado activo (se ejecuta automáticamente)</td>
                    </tr>
                    <tr>
                        <td><strong>Mensualidad Lesionado</strong></td>
                        <td>Cobro mensual reducido a socios en estado lesionado (se ejecuta automáticamente)</td>
                    </tr>
                    <tr>
                        <td><strong>Afiliación</strong></td>
                        <td>Tarifa inicial que pagan los nuevos socios al inscribirse</td>
                    </tr>
                    <tr>
                        <td><strong>Inscripción</strong></td>
                        <td>Tarifa administrativa complementaria</td>
                    </tr>
                </table>
                
                <h6 class="mt-3">¿Cómo editar una tarifa?</h6>
                <ol>
                    <li>Haz clic en el botón editar (<i class="fas fa-edit"></i>) de la tarifa que deseas cambiar</li>
                    <li>Cambia el monto según sea necesario</li>
                    <li>Puedes actualizar la descripción si lo deseas</li>
                    <li>Haz clic en "Guardar Cambios"</li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <strong>Importante:</strong> Los cambios se aplican inmediatamente a:
                    <ul class="mb-0 mt-2">
                        <li>Próximos cobros mensuales</li>
                        <li>Nuevos registros de socios</li>
                        <li>Generación de comprobantes</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="/corve/public/js/tarifas.js?v=<?= time(); ?>"></script>

<?php include_once BASE_PATH . '/includes/footer.php'; ?>
