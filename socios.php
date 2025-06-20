<?php
// Incluir cabecera
include_once 'includes/header.php';

// Incluir el modelo Socio
require_once 'models/Socio.php';

// Obtener la lista de socios
try {
    $socio = new Socio();
    $stmt = $socio->read();
    $socios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_socios = count($socios);
} catch (Exception $e) {
    $error = "Error al cargar los socios: " . $e->getMessage();
}
?>

<!-- Contenido de la página -->
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (solo uno) -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestión de Socios</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#nuevoSocioModal">
                        <i class="fas fa-plus"></i> Nuevo Socio
                    </button>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Resumen -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total de Socios</h5>
                            <p class="card-text display-6"><?php echo $total_socios ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Socios Activos</h5>
                            <p class="card-text display-6">
                                <?php 
                                    $activos = isset($socios) ? array_filter($socios, function($s) { 
                                        return ($s['estado'] ?? '') === 'activo'; 
                                    }) : []; 
                                    echo count($activos);
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Saldo Total</h5>
                            <p class="card-text display-6">
                                $
                                <?php 
                                    $saldo_total = isset($socios) ? array_sum(array_column($socios, 'saldo')) : 0;
                                    echo number_format($saldo_total, 2);
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de socios -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Listado de Socios</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($socios) && count($socios) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Documento</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($socios as $socio): 
                                        // Formatear fecha de nacimiento para el input date
                                        $fecha_nacimiento = !empty($socio['fecha_nacimiento']) ? date('Y-m-d', strtotime($socio['fecha_nacimiento'])) : '';
                                        
                                        $estadoClase = '';
                                        $estadoTexto = '';
                                        switch($socio['estado'] ?? 'activo') {
                                            case 'activo':
                                                $estadoClase = 'bg-success';
                                                $estadoTexto = 'Activo';
                                                break;
                                            case 'lesionado':
                                                $estadoClase = 'bg-warning';
                                                $estadoTexto = 'Lesionado';
                                                break;
                                            case 'retirado':
                                                $estadoClase = 'bg-secondary';
                                                $estadoTexto = 'Retirado';
                                                break;
                                            default:
                                                $estadoClase = 'bg-info';
                                                $estadoTexto = 'Desconocido';
                                        }
                                    ?>
                                    <tr data-estado="<?php echo $socio['estado'] ?? 'activo'; ?>">
                                        <td><?php echo htmlspecialchars($socio['id']); ?></td>
                                        <td><?php echo htmlspecialchars($socio['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($socio['documento']); ?></td>
                                        <td><?php echo htmlspecialchars($socio['email']); ?></td>
                                        <td><?php echo htmlspecialchars($socio['telefono']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $estadoClase; ?>">
                                                <?php echo $estadoTexto; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-primary btn-editar-socio" 
                                                        data-id="<?php echo $socio['id']; ?>"
                                                        title="Editar socio">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item btn-cambiar-estado" href="#" 
                                                           data-id="<?php echo $socio['id']; ?>" 
                                                           data-accion="activar"
                                                           <?php echo ($socio['estado'] ?? '') === 'activo' ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-check-circle text-success me-2"></i>Activar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item btn-cambiar-estado" href="#" 
                                                           data-id="<?php echo $socio['id']; ?>" 
                                                           data-accion="lesionar"
                                                           <?php echo ($socio['estado'] ?? '') === 'lesionado' ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-procedures text-warning me-2"></i>Marcar como lesionado
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger btn-cambiar-estado" href="#" 
                                                           data-id="<?php echo $socio['id']; ?>" 
                                                           data-accion="retirar"
                                                           <?php echo ($socio['estado'] ?? '') === 'retirado' ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-sign-out-alt me-2"></i>Dar de baja
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No se encontraron socios registrados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="confirmarEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar al socio <strong id="nombreSocioEliminar"></strong>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarEliminarBtn">Eliminar</button>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar socio -->
<div class="modal fade" id="editarSocioModal" tabindex="-1" aria-labelledby="editarSocioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editarSocioModalLabel">Editar Socio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEditarSocio" action="controllers/socio_controller.php" method="POST">
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="id" id="editar_id">
                <div class="modal-body">
                    <div id="editarMensajeError" class="alert alert-danger d-none"></div>
                    <div class="row g-3">
                        <!-- Datos Personales -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Datos Personales</h6>
                            <div class="mb-3">
                                <label for="editar_nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editar_nombre" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="editar_documento" class="form-label">Documento <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editar_documento" name="documento" required>
                            </div>
                            <div class="mb-3">
                                <label for="editar_email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="editar_email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="editar_telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="editar_telefono" name="telefono" required>
                            </div>
                        </div>
                        
                        <!-- Dirección y Fecha de Nacimiento -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Información Adicional</h6>
                            <div class="mb-3">
                                <label for="editar_direccion" class="form-label">Dirección</label>
                                <textarea class="form-control" id="editar_direccion" name="direccion" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editar_fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="editar_fecha_nacimiento" name="fecha_nacimiento">
                            </div>
                            <div class="mb-3">
                                <label for="editar_entidad_salud" class="form-label">Entidad de Salud</label>
                                <input type="text" class="form-control" id="editar_entidad_salud" name="entidad_salud">
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="editar_afiliado" name="afiliado" value="1">
                                <!--<label class="form-check-label" for="editar_afiliado">Afiliado activo</label>-->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .dropdown-item.disabled {
        opacity: 0.6;
        pointer-events: none;
    }
    .dropdown-item.text-danger {
        color: #dc3545 !important;
    }
    .dropdown-item.text-danger:hover {
        background-color: rgba(220, 53, 69, 0.1);
    }
</style>

<script>
// Script para manejar la eliminación de socios y cambios de estado
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let socioIdSeleccionado = null;
    let accionSeleccionada = null;
    
    // Elementos del DOM
    const modalCambioEstado = new bootstrap.Modal(document.getElementById('confirmarCambioEstado'));
    const mensajeCambioEstado = document.getElementById('mensajeCambioEstado');
    const motivoCambioEstado = document.getElementById('motivoCambioEstado');
    const btnConfirmarCambio = document.getElementById('confirmarCambioEstadoBtn');
    
    // Mapeo de acciones a textos descriptivos
    const accionesTexto = {
        'activar': 'activar',
        'lesionar': 'marcar como lesionado',
        'retirar': 'dar de baja'
    };
    
    // Manejador para los botones de cambio de estado
    document.querySelectorAll('.btn-cambiar-estado').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (this.hasAttribute('disabled')) {
                return;
            }
            
            const socioId = this.dataset.id;
            const accion = this.dataset.accion;
            const nombreSocio = this.closest('tr').querySelector('td:nth-child(2)').textContent;
            
            // Configurar el modal según la acción
            let mensaje = '';
            
            switch(accion) {
                case 'activar':
                    mensaje = `¿Estás seguro de que deseas activar al socio <strong>${nombreSocio}</strong>?`;
                    break;
                case 'lesionar':
                    mensaje = `¿Estás seguro de que deseas marcar como lesionado al socio <strong>${nombreSocio}</strong>?`;
                    mensaje += '<br><small class="text-muted">El socio solo pagará $10,000 de mantenimiento mensual.</small>';
                    break;
                case 'retirar':
                    mensaje = `¿Estás seguro de que deseas dar de baja al socio <strong>${nombreSocio}</strong>?`;
                    mensaje += '<br><small class="text-danger">El socio no tendrá que pagar más mensualidades.</small>';
                    break;
            }
            
            // Actualizar el modal
            mensajeCambioEstado.innerHTML = mensaje;
            motivoCambioEstado.value = '';
            
            // Guardar los datos para usar después
            socioIdSeleccionado = socioId;
            accionSeleccionada = accion;
            
            // Mostrar el modal
            modalCambioEstado.show();
        });
    });
    
    // Manejador para confirmar el cambio de estado
    btnConfirmarCambio.addEventListener('click', function() {
        if (!socioIdSeleccionado || !accionSeleccionada) return;
        
        const motivo = motivoCambioEstado.value.trim();
        const notificar = document.getElementById('notificarSocio').checked;
        
        // Mostrar indicador de carga
        const btnOriginal = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        
        // Enviar la petición al servidor
        fetch('acciones_socio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                accion: accionSeleccionada,
                socio_id: socioIdSeleccionado,
                motivo: motivo,
                notificar: notificar
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message || 'Estado actualizado correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Recargar la página después de 1.5 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Error al actualizar el estado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Ocurrió un error al actualizar el estado',
                confirmButtonText: 'Aceptar'
            });
        })
        .finally(() => {
            // Restaurar el botón
            this.disabled = false;
            this.innerHTML = btnOriginal;
            
            // Cerrar el modal
            modalCambioEstado.hide();
            
            // Limpiar variables
            socioIdSeleccionado = null;
            accionSeleccionada = null;
        });
    });
    
    // Limpiar el modal cuando se cierre
    document.getElementById('confirmarCambioEstado').addEventListener('hidden.bs.modal', function () {
        mensajeCambioEstado.innerHTML = '';
        motivoCambioEstado.value = '';
        document.getElementById('notificarSocio').checked = false;
        socioIdSeleccionado = null;
        accionSeleccionada = null;
    });
    
    // Código existente para la eliminación de socios
    const modalEliminar = new bootstrap.Modal(document.getElementById('confirmarEliminar'));
    const modalEditar = new bootstrap.Modal(document.getElementById('editarSocioModal'));
    let socioIdAEliminar = null;
    // Configurar el modal de confirmación de eliminación
    
    // Manejar clic en botones de eliminar
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-eliminar')) {
            const btn = e.target.closest('.btn-eliminar');
            socioIdAEliminar = btn.getAttribute('data-id');
            document.getElementById('socioEliminarId').textContent = socioIdAEliminar;
            document.getElementById('socioEliminarNombre').textContent = btn.getAttribute('data-nombre');
        }
    });
    
    // Manejar clic en botones de editar
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-editar')) {
            const btn = e.target.closest('.btn-editar');
            
            // Llenar el formulario con los datos del socio
            document.getElementById('editar_id').value = btn.getAttribute('data-id');
            document.getElementById('editar_nombre').value = btn.getAttribute('data-nombre');
            document.getElementById('editar_documento').value = btn.getAttribute('data-documento');
            document.getElementById('editar_email').value = btn.getAttribute('data-email');
            document.getElementById('editar_telefono').value = btn.getAttribute('data-telefono');
            document.getElementById('editar_direccion').value = btn.getAttribute('data-direccion');
            document.getElementById('editar_fecha_nacimiento').value = btn.getAttribute('data-fecha_nacimiento');
            document.getElementById('editar_entidad_salud').value = btn.getAttribute('data-entidad_salud');
            document.getElementById('editar_saldo').value = btn.getAttribute('data-saldo');
            
            // Marcar checkbox si está afiliado
            const afiliado = btn.getAttribute('data-afiliado') === '1';
            document.getElementById('editar_afiliado').checked = afiliado;
            
            modalEditar.show();
        }
    });
    
    // Confirmar eliminación
    document.getElementById('confirmarEliminarBtn').addEventListener('click', function() {
        if (socioIdAEliminar) {
            // Realizar la petición AJAX para eliminar
            fetch(`controllers/socio_controller.php?id=${socioIdAEliminar}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar notificación de éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: 'El socio ha sido eliminado correctamente.',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        // Recargar la página después de eliminar
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Error al eliminar el socio');
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo eliminar el socio: ' + error.message,
                });
            });
        }
        modalEliminar.hide();
    });
    
    // Manejar envío del formulario de edición
    document.getElementById('formEditarSocio').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formElement = this;
        const formData = new FormData(formElement);
        const id = formData.get('id');
        
        // Mostrar indicador de carga
        const submitBtn = formElement.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
        
        // Convertir FormData a objeto para enviar como JSON
        const data = {};
        formData.forEach((value, key) => {
            // Manejar correctamente los checkboxes
            if (key === 'afiliado') {
                data[key] = 1; // Siempre es 1 si está presente
            } else {
                data[key] = value;
            }
        });
        
        console.log('Enviando datos de actualización...');
        console.log('URL:', `controllers/socio_controller.php?id=${id}`);
        console.log('Datos:', data);
        
        fetch(`controllers/socio_controller.php?id=${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('Estado de la respuesta:', response.status);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Error en la respuesta del servidor:', text);
                    try {
                        // Intentar parsear como JSON
                        const json = JSON.parse(text);
                        throw new Error(json.message || `Error HTTP: ${response.status}`);
                    } catch (e) {
                        throw new Error(text || `Error HTTP: ${response.status}`);
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos de respuesta:', data);
            if (data.success) {
                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Los cambios se han guardado correctamente',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    // Cerrar el modal
                    modalEditar.hide();
                    // Recargar la página para ver los cambios
                    window.location.reload();
                });
            } else {
                // Mostrar mensaje de error
                const errorMsg = data.message || 'Error desconocido al guardar los cambios';
                console.error('Error en la respuesta:', errorMsg);
                throw new Error(errorMsg);
            }
        })
        .catch(error => {
            console.error('Error en la petición:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron guardar los cambios: ' + (error.message || 'Error desconocido'),
                confirmButtonText: 'Entendido'
            });
        })
        .finally(() => {
            // Restaurar el botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
    
    // Cerrar modales al hacer clic en el botón de cancelar
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.closest('.modal').id;
            const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
            modal.hide();
        });
    });
});
</script>

<!-- Modal para crear nuevo socio -->
<div class="modal fade" id="nuevoSocioModal" tabindex="-1" aria-labelledby="nuevoSocioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="nuevoSocioModalLabel">Nuevo Socio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formNuevoSocio" action="controllers/socio_controller.php" method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Datos Personales -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Datos Personales</h6>
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombres Completos *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="documento" class="form-label">Documento *</label>
                                    <input type="text" class="form-control" id="documento" name="documento" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_nacimiento" class="form-label">Fecha Nacimiento</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion">
                            </div>
                        </div>

                        <!-- Contacto -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Contacto</h6>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" required>
                            </div>
                            <div class="mb-3">
                                <label for="telefono_emergencia" class="form-label">Teléfono de Emergencia</label>
                                <input type="tel" class="form-control" id="telefono_emergencia" name="telefono_emergencia">
                            </div>
                        </div>

                        <!-- Información Adicional -->
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">Información Adicional</h6>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="entidad_salud" class="form-label">Entidad de Salud</label>
                                    <input type="text" class="form-control" id="entidad_salud" name="entidad_salud">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Cuotas Iniciales</label>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">Afiliación</span>
                                        <input type="text" class="form-control bg-light" value="$100,000" disabled>
                                        <input type="hidden" name="cuota_afiliacion" value="100000">
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text">Inscripción</span>
                                        <input type="text" class="form-control bg-light" value="$45,000" disabled>
                                        <input type="hidden" name="cuota_inscripcion" value="45000">
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="fecha_afiliacion" class="form-label">Fecha de Afiliación *</label>
                                    <input type="date" class="form-control" id="fecha_afiliacion" name="fecha_afiliacion" required>
                                    <div class="form-text">Fecha desde la que se generarán los cobros mensuales</div>
                                    
                                    <label for="saldo" class="form-label mt-2">Saldo Inicial</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="saldo" name="saldo" value="145000" step="1000" min="145000" readonly>
                                    </div>
                                    <div class="form-text mb-2">Incluye afiliación ($100,000) e inscripción ($45,000)</div>
                                    
                                    <div class="alert alert-info p-2 small">
                                        <i class="fas fa-info-circle"></i> Cuota mensual: $45,000
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3 d-flex align-items-end">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="afiliado" name="afiliado" value="1" checked>
                                        <label class="form-check-label" for="afiliado">Afiliado Activo</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Socio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para confirmar cambio de estado -->
<div class="modal fade" id="confirmarCambioEstado" tabindex="-1" aria-labelledby="confirmarCambioEstadoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="confirmarCambioEstadoLabel">Confirmar cambio de estado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="mensajeCambioEstado"></div>
                <div class="mb-3">
                    <label for="motivoCambioEstado" class="form-label">Motivo del cambio (opcional):</label>
                    <textarea class="form-control" id="motivoCambioEstado" rows="3"></textarea>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="notificarSocio">
                    <label class="form-check-label" for="notificarSocio">
                        Notificar al socio por correo electrónico
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmarCambioEstadoBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Script para manejar los formularios de socios
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales para el cambio de estado
    let socioIdCambioEstado = null;
    let accionCambioEstado = null;
    let modalCambioEstado = null;
    
    // Inicializar el modal de confirmación
    const modalElement = document.getElementById('confirmarCambioEstado');
    if (modalElement) {
        modalCambioEstado = new bootstrap.Modal(modalElement);
    }
    
    // Manejador para los botones de cambio de estado
    document.querySelectorAll('.btn-cambiar-estado').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Verificar si el botón está deshabilitado
            if (this.hasAttribute('disabled')) {
                return;
            }
            
            // Obtener datos del botón
            socioIdCambioEstado = this.getAttribute('data-id');
            accionCambioEstado = this.getAttribute('data-accion');
            
            // Configurar el mensaje según la acción
            const mensajes = {
                'activar': '¿Estás seguro de que deseas activar a este socio?',
                'lesionar': '¿Estás seguro de que deseas marcar a este socio como lesionado?',
                'retirar': '¿Estás seguro de que deseas dar de baja a este socio?'
            };
            
            document.getElementById('mensajeCambioEstado').innerHTML = `<p>${mensajes[accionCambioEstado] || '¿Confirmas esta acción?'}</p>`;
            document.getElementById('motivoCambioEstado').value = '';
            document.getElementById('notificarSocio').checked = false;
            
            // Mostrar el modal
            modalCambioEstado.show();
        });
    });
    
    // Manejador para el botón de confirmar cambio de estado
    document.getElementById('confirmarCambioEstadoBtn')?.addEventListener('click', function() {
        if (!socioIdCambioEstado || !accionCambioEstado) return;
        
        const motivo = document.getElementById('motivoCambioEstado').value.trim();
        const notificar = document.getElementById('notificarSocio').checked;
        const btnConfirmar = this;
        const textoOriginal = btnConfirmar.innerHTML;
        
        // Mostrar indicador de carga
        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        
        // Enviar la solicitud al servidor
        fetch('controllers/SocioEstadoController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                accion: accionCambioEstado,
                socio_id: socioIdCambioEstado,
                motivo: motivo,
                notificar: notificar
            })
        })
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al analizar la respuesta JSON:', e);
                console.error('Respuesta del servidor:', text);
                throw new Error('La respuesta del servidor no es un JSON válido');
            }
        })
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message || 'El estado del socio se ha actualizado correctamente',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    // Recargar la página para ver los cambios
                    window.location.reload();
                });
            } else {
                throw new Error(data.message || 'Error al actualizar el estado del socio');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Ocurrió un error al procesar la solicitud',
                confirmButtonText: 'Aceptar'
            });
        })
        .finally(() => {
            // Cerrar el modal y restaurar el botón
            modalCambioEstado.hide();
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = textoOriginal;
        });
    });
    
    // Limpiar variables al cerrar el modal
    document.getElementById('confirmarCambioEstado')?.addEventListener('hidden.bs.modal', function () {
        socioIdCambioEstado = null;
        accionCambioEstado = null;
        document.getElementById('mensajeCambioEstado').innerHTML = '';
        document.getElementById('motivoCambioEstado').value = '';
        document.getElementById('notificarSocio').checked = false;
    });
    // Variables globales
    const editarSocioModal = document.getElementById('editarSocioModal');
    const formEditarSocio = document.getElementById('formEditarSocio');
    const editarMensajeError = document.getElementById('editarMensajeError');
    
    // Función para cargar los datos del socio en el formulario de edición
    function cargarDatosSocioEnModal(socioId) {
        console.log('Iniciando carga de datos para el socio ID:', socioId);
        
        // Mostrar indicador de carga
        const botonCargando = document.querySelector('#editarSocioModal .btn-primary');
        const textoOriginal = botonCargando.innerHTML;
        botonCargando.disabled = true;
        botonCargando.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...';
        
        // Ocultar mensaje de error si está visible
        editarMensajeError.classList.add('d-none');
        
        // URL de la API
        const url = `controllers/socio_controller.php/${socioId}`;
        console.log('Realizando petición a:', url);
        
        // Realizar petición para obtener los datos del socio
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Respuesta recibida. Estado:', response.status);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Error en la respuesta:', text);
                    throw new Error(`Error al cargar los datos del socio: ${response.status} ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            
            if (data.success && data.data) {
                const socio = data.data;
                console.log('Datos del socio a cargar:', socio);
                
                // Llenar el formulario con los datos del socio
                document.getElementById('editar_id').value = socio.id || '';
                document.getElementById('editar_nombre').value = socio.nombre || '';
                document.getElementById('editar_documento').value = socio.documento || '';
                document.getElementById('editar_email').value = socio.email || '';
                document.getElementById('editar_telefono').value = socio.telefono || '';
                document.getElementById('editar_direccion').value = socio.direccion || '';
                document.getElementById('editar_fecha_nacimiento').value = socio.fecha_nacimiento || '';
                document.getElementById('editar_entidad_salud').value = socio.entidad_salud || '';
                document.getElementById('editar_afiliado').checked = socio.afiliado || false;
                
                // Mostrar el modal
                console.log('Mostrando modal de edición');
                const modal = new bootstrap.Modal(editarSocioModal);
                modal.show();
                
                // Hacer scroll hasta el inicio del formulario
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                throw new Error(data.message || 'No se recibieron datos válidos del socio');
            }
        })
        .catch(error => {
            console.error('Error al cargar los datos del socio:', error);
            editarMensajeError.textContent = error.message || 'Error al cargar los datos del socio. Por favor, intente de nuevo.';
            editarMensajeError.classList.remove('d-none');
            
            // Mostrar el modal de todos modos para ver el error
            const modal = new bootstrap.Modal(editarSocioModal);
            modal.show();
        })
        .finally(() => {
            // Restaurar el botón
            botonCargando.disabled = false;
            botonCargando.innerHTML = textoOriginal;
        });
    }
    
    // Manejador para el envío del formulario de edición
    if (formEditarSocio) {
        formEditarSocio.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar formulario
            const formData = new FormData(formEditarSocio);
            const formObject = Object.fromEntries(formData.entries());
            
            // Validar campos requeridos
            if (!formObject.nombre || !formObject.documento || !formObject.email || !formObject.telefono) {
                editarMensajeError.textContent = 'Por favor complete todos los campos requeridos';
                editarMensajeError.classList.remove('d-none');
                return;
            }
            
            // Validar formato de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formObject.email)) {
                editarMensajeError.textContent = 'Por favor ingrese un correo electrónico válido';
                editarMensajeError.classList.remove('d-none');
                return;
            }
            
            // Mostrar indicador de carga
            const botonGuardar = formEditarSocio.querySelector('button[type="submit"]');
            const textoOriginal = botonGuardar.innerHTML;
            botonGuardar.disabled = true;
            botonGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            
            // Ocultar mensaje de error
            editarMensajeError.classList.add('d-none');
            
            // Enviar los datos al servidor
            fetch('controllers/socio_controller.php/' + formObject.id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formObject)
            })
            .then(async response => {
                const text = await response.text();
                try {
                    // Intentar analizar la respuesta como JSON
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error al analizar la respuesta JSON:', e);
                    console.error('Respuesta del servidor:', text);
                    throw new Error('La respuesta del servidor no es un JSON válido');
                }
            })
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message || 'Los cambios se han guardado correctamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Recargar la página para ver los cambios
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Error al guardar los cambios');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                editarMensajeError.textContent = error.message || 'Error al guardar los cambios';
                editarMensajeError.classList.remove('d-none');
                
                // Hacer scroll hasta el mensaje de error
                editarMensajeError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            })
            .finally(() => {
                // Restaurar el botón
                botonGuardar.disabled = false;
                botonGuardar.innerHTML = textoOriginal;
            });
        });
    }
    
    // Manejador para los botones de editar
    const botonesEditar = document.querySelectorAll('.btn-editar-socio');
    console.log('Botones de editar encontrados:', botonesEditar.length);
    
    botonesEditar.forEach(btn => {
        btn.addEventListener('click', function(e) {
            console.log('Botón de editar clickeado');
            const socioId = this.getAttribute('data-id');
            console.log('ID del socio a editar:', socioId);
            
            if (socioId) {
                console.log('Llamando a cargarDatosSocioEnModal con ID:', socioId);
                cargarDatosSocioEnModal(socioId);
            } else {
                console.error('No se encontró el ID del socio');
            }
            
            // Prevenir el comportamiento por defecto
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    });
    
    // Script para manejar el formulario de nuevo socio
    // Establecer la fecha de afiliación por defecto a hoy
    const fechaHoy = new Date().toISOString().split('T')[0];
    document.getElementById('fecha_afiliacion').value = fechaHoy;
    const formNuevoSocio = document.getElementById('formNuevoSocio');
    
    if (formNuevoSocio) {
        formNuevoSocio.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Agregar el monto total al formulario
            const formData = new FormData(this);
            const afiliacion = parseFloat(document.querySelector('input[name="cuota_afiliacion"]').value);
            const inscripcion = parseFloat(document.querySelector('input[name="cuota_inscripcion"]').value);
            const total = afiliacion + inscripcion;
            formData.append('monto_total', total);
            
            console.log('Enviando datos del formulario...');
            console.log('URL:', 'controllers/socio_controller.php');
            
            // Mostrar los datos del formulario en la consola
            console.log('=== Datos del formulario ===');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            // Mostrar mensaje de carga
            const btnSubmit = document.querySelector('#formNuevoSocio button[type="submit"]');
            const btnOriginalText = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
            
            // Convertir FormData a objeto
            const formObject = {};
            formData.forEach((value, key) => {
                // Si el campo ya existe, convertirlo en array
                if (formObject[key] !== undefined) {
                    if (!Array.isArray(formObject[key])) {
                        formObject[key] = [formObject[key]];
                    }
                    formObject[key].push(value);
                } else {
                    formObject[key] = value;
                }
            });
            
            console.log('Datos a enviar:', formObject);
            
            // Enviar los datos al servidor como JSON
            fetch('controllers/socio_controller.php', {
                method: 'POST',
                body: JSON.stringify(formObject),
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'  // Importante para mantener la sesión
            })
            .then(response => {
                console.log('Estado de la respuesta:', response.status);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Error en la respuesta del servidor:', text);
                        throw new Error(`Error HTTP! estado: ${response.status}, respuesta: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos de respuesta:', data);
                if (data.success) {
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Socio registrado correctamente',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Cerrar el modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('nuevoSocioModal'));
                        modal.hide();
                        // Recargar la página para ver los cambios
                        window.location.reload();
                    });
                } else {
                    // Mostrar mensaje de error
                    const errorMsg = data.message || 'Error desconocido';
                    console.error('Error en la respuesta:', errorMsg);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo registrar el socio: ' + errorMsg,
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                console.error('Error en la petición:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor. Por favor, verifica tu conexión e inténtalo de nuevo.',
                    confirmButtonText: 'Entendido'
                });
            })
            .finally(() => {
                // Restaurar el botón
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = btnOriginalText;
            });
        });
    }
});
</script>

<?php
// Incluir pie de página
include_once 'includes/footer.php';
?>
