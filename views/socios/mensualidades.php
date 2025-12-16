<?php
// mensualidades.php - Vista de gestión de mensualidades - NUEVO SISTEMA
include_once '../../includes/header.php';
// Incluir lógica PHP de mensualidades desde carpeta logic
include_once __DIR__ . '/../../logic/mensualidades.logic.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestión de Mensualidades</h1>
                <a href="#" id="btnCobroMensual" class="btn btn-success btn-sm"><i class="fas fa-coins"></i> Ejecutar cobro mensual</a>
            </div>

            <!-- Resumen General -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Pagado</h5>
                            <p class="card-text display-6">$<?= number_format($totalGlobalPagado, 0, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Socios Activos</h5>
                            <p class="card-text display-6"><?= count($allSocios) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Próximos Cobros -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Próximos Cobros Programados</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($sociosProximoCobro)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Socio</th>
                                        <th>Documento</th>
                                        <th>Próximo Cobro</th>
                                        <th>Días para Cobro</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sociosProximoCobro as $socio): 
                                        $diasRestantes = $socio['dias_para_cobro'];
                                        $badgeClass = ($diasRestantes <= 3) ? 'bg-danger' : (($diasRestantes <= 7) ? 'bg-warning text-dark' : 'bg-info');
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($socio['nombre']) ?></strong></td>
                                        <td><?= htmlspecialchars($socio['documento']) ?></td>
                                        <td><?= $socio['proximo_cobro'] ?></td>
                                        <td><span class="badge <?= $badgeClass ?>"><?= $diasRestantes ?> días</span></td>
                                        <td>
                                            <?php 
                                            $estadoClass = ($socio['estado'] === 'activo') ? 'bg-success' : 'bg-secondary';
                                            $estadoTexto = ucfirst($socio['estado']);
                                            ?>
                                            <span class="badge <?= $estadoClass ?>"><?= $estadoTexto ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">No hay próximos cobros programados.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historial de Mensualidades -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Historial de Pagos de Mensualidades</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaMensualidades" class="table table-striped table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>ID Pago</th>
                                    <th>Socio</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagosFiltrados as $pago): ?>
                                <tr>
                                    <td><?= htmlspecialchars($pago['id']) ?></td>
                                    <td><strong><?= htmlspecialchars($pago['nombre'] ?? 'Sin nombre') ?></strong></td>
                                    <td class="text-success fw-bold">$<?= number_format($pago['monto'], 0, ',', '.') ?></td>
                                    <td><?= (new DateTime($pago['fecha']))->format('d/m/Y H:i') ?></td>
                                    <td><?= htmlspecialchars($pago['concepto']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Resumen por Socio -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Resumen de Mensualidades por Socio</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaResumenMensualidades" class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Documento</th>
                                    <th>F. Ingreso</th>
                                    <th>Próximo Cobro</th>
                                    <th>Meses Cobrados</th>
                                    <th>Total Pagado</th>
                                    <th>Deuda Actual</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sociosMensualidades as $socio): 
                                    $deudaClass = ($socio['saldo'] > 0) ? 'text-danger fw-bold' : 'text-success fw-bold';
                                    $estadoClass = ($socio['estado'] === 'activo') ? 'badge bg-success' : ($socio['estado'] === 'lesionado' ? 'badge bg-warning text-dark' : 'badge bg-secondary');
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($socio['id']) ?></td>
                                    <td><?= htmlspecialchars($socio['nombre']) ?></td>
                                    <td><?= htmlspecialchars($socio['documento']) ?></td>
                                    <td>
                                        <?php 
                                        if ($socio['fecha_afiliacion']) {
                                            echo (new DateTime($socio['fecha_afiliacion']))->format('d/m/Y');
                                        } else {
                                            echo '<span class="text-muted">N/A</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($socio['proximo_cobro']) {
                                            $diasClass = ($socio['dias_para_cobro'] <= 3) ? 'badge bg-danger' : (($socio['dias_para_cobro'] <= 7) ? 'badge bg-warning text-dark' : 'badge bg-info');
                                            echo '<span class="' . $diasClass . '">' . $socio['proximo_cobro'] . '</span>';
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $socio['meses_cobrados'] ?></span>
                                    </td>
                                    <td class="text-success fw-bold">$<?= number_format($socio['total_pagado'], 0, ',', '.') ?></td>
                                    <td class="<?= $deudaClass ?>">$<?= number_format($socio['saldo'], 0, ',', '.') ?></td>
                                    <td><span class="<?= $estadoClass ?>"><?= ucfirst($socio['estado']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Cobro Exitoso -->
            <div class="modal fade" id="modalCobroExito" tabindex="-1" aria-labelledby="modalCobroExitoLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalCobroExitoLabel"><i class="fas fa-check-circle"></i> Cobro Mensual Realizado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body">
                    <p id="mensajeCobroExito"></p>
                    <div id="detallesCobroExito"></div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Aceptar</button>
                  </div>
                </div>
              </div>
            </div>
        </main>
    </div>
</div>

<script src="../../public/js/mensualidades.js?v=<?= time(); ?>"></script>

<?php include_once '../../includes/footer.php'; ?>
