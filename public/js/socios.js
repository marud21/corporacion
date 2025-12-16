// socios.js - Gestión de socios mejorada
document.addEventListener('DOMContentLoaded', function() {
    console.log('JS socios.js cargado');

    try {
        // Inicializar DataTable
        if (window.jQuery && $.fn.DataTable) {
            $('#tablaSocios').DataTable({
                language: {
                    url: '/corve/public/js/es-ES.json'
                },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                order: [[1, 'asc']],
                responsive: true,
                columnDefs: [
                    { orderable: false, targets: -1 } // Columna de acciones no ordenable
                ]
            });
            console.log('✓ DataTable inicializado');
        }
    } catch (error) {
        console.error('Error inicializando DataTable:', error);
    }

    // Filtrar socios
    document.getElementById('btn_filtrar')?.addEventListener('click', function() {
        filtrarSocios();
    });

    // Permitir Enter en búsqueda
    document.getElementById('buscar_socio')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            filtrarSocios();
        }
    });

    // Editar y eliminar socio
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-editar')) {
            const id = e.target.closest('.btn-editar').dataset.id;
            cargarSocioParaEditar(id);
        }
        
        // Eliminar socio
        if (e.target.closest('.btn-eliminar')) {
            const id = e.target.closest('.btn-eliminar').dataset.id;
            const nombre = e.target.closest('tr').querySelector('td:nth-child(2)')?.textContent.trim();
            if (confirm(`¿Estás seguro de que deseas eliminar a ${nombre}?`)) {
                eliminarSocio(id);
            }
        }

        // Ver detalles del socio
        if (e.target.closest('.btn-ver')) {
            const id = e.target.closest('.btn-ver').dataset.id;
            verSocioDetalles(id);
        }
    });

    // Formulario nuevo socio
    const formNuevoSocio = document.getElementById('formNuevoSocio');
    if (formNuevoSocio) {
        formNuevoSocio.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarNuevoSocio();
        });
    }

    // Formulario editar socio
    const formEditarSocio = document.getElementById('formEditarSocio');
    if (formEditarSocio) {
        formEditarSocio.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarCambiosSocio();
        });
    }

    // Limpiar modales al cerrar
    document.getElementById('nuevoSocioModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('formNuevoSocio')?.reset();
        document.getElementById('mensajeError')?.classList.add('d-none');
        document.getElementById('mensajeExito')?.classList.add('d-none');
    });

    document.getElementById('editarSocioModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('mensajeErrorEditar')?.classList.add('d-none');
    });

    // Botón editar desde modal de ver
    document.getElementById('btnEditarDesdeVer')?.addEventListener('click', function() {
        const id = this.dataset.id;
        console.log('Abriendo edición para socio:', id);
        
        // Cerrar el modal de ver
        const verModal = bootstrap.Modal.getInstance(document.getElementById('verSocioModal'));
        if (verModal) {
            verModal.hide();
        }
        
        // Cargar el socio en el modal de editar
        cargarSocioParaEditar(id);
    });

    // Vista previa de foto en edición
    document.getElementById('edit_foto_documento')?.addEventListener('change', function(e) {
        const archivo = e.target.files?.[0];
        if (!archivo) return;
        
        const reader = new FileReader();
        reader.onload = function(event) {
            const editFotoPreview = document.getElementById('editFotoPreview');
            const editSinFoto = document.getElementById('editSinFoto');
            
            editFotoPreview.src = event.target.result;
            editFotoPreview.style.display = 'block';
            editSinFoto.style.display = 'none';
            console.log('Preview de nueva foto cargado');
        };
        reader.readAsDataURL(archivo);
    });

    console.log('✓ Funcionalidades de socios inicializadas');
});

/**
 * Filtrar socios por nombre/documento y estado
 */
function filtrarSocios() {
    const buscar = document.getElementById('buscar_socio').value.toLowerCase();
    const estado = document.getElementById('filtro_estado').value.toLowerCase();
    
    const table = $('#tablaSocios').DataTable();
    table.columns(3).search(estado ? estado : '', true, false);
    
    if (buscar) {
        table.search(buscar).draw();
    } else {
        table.search('').draw();
    }
    
    console.log('Filtros aplicados - Buscar:', buscar, 'Estado:', estado);
}

/**
 * Cargar datos de un socio para editar
 */
function cargarSocioParaEditar(id) {
    console.log('Cargando socio ID:', id);
    
    fetch('/corve/controllers/socio_controller.php?id=' + id)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos del socio:', data);
            
            if (data.success) {
                const socio = data.data;
                
                // Llenar formulario
                document.getElementById('socio_id').value = socio.id;
                document.getElementById('edit_nombre').value = socio.nombre || '';
                document.getElementById('edit_documento').value = socio.documento || '';
                document.getElementById('edit_fecha_nacimiento').value = socio.fecha_nacimiento || '';
                document.getElementById('edit_email').value = socio.email || '';
                document.getElementById('edit_telefono').value = socio.telefono || '';
                document.getElementById('edit_fecha_afiliacion').value = socio.fecha_afiliacion || '';
                document.getElementById('edit_estado').value = socio.estado || 'activo';
                document.getElementById('edit_direccion').value = socio.direccion || '';
                document.getElementById('edit_entidad_salud').value = socio.entidad_salud || '';
                document.getElementById('edit_observaciones').value = socio.observaciones || '';
                
                // Cargar foto actual si existe
                const editFotoPreview = document.getElementById('editFotoPreview');
                const editSinFoto = document.getElementById('editSinFoto');
                
                if (socio.documento_pdf && socio.documento_pdf.trim() !== '') {
                    editFotoPreview.src = '/corve/' + socio.documento_pdf;
                    editFotoPreview.style.display = 'block';
                    editSinFoto.style.display = 'none';
                    console.log('Foto cargada:', editFotoPreview.src);
                } else {
                    editFotoPreview.style.display = 'none';
                    editSinFoto.style.display = 'block';
                    console.log('No hay foto actual');
                }
                
                // Limpiar input de archivo
                document.getElementById('edit_foto_documento').value = '';
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('editarSocioModal'));
                modal.show();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar el socio: ' + error.message);
        });
}

/**
 * Guardar nuevo socio
 */
function guardarNuevoSocio() {
    const form = document.getElementById('formNuevoSocio');
    
    // Obtener archivo de documento
    const fotDocumentoInput = document.getElementById('foto_documento');
    if (!fotDocumentoInput.files || fotDocumentoInput.files.length === 0) {
        const mensajeError = document.getElementById('mensajeError');
        mensajeError.classList.remove('d-none');
        mensajeError.textContent = 'Debes seleccionar una foto del documento de identidad';
        return;
    }
    
    // Validar tipo de archivo
    const archivo = fotDocumentoInput.files[0];
    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
    if (!tiposPermitidos.includes(archivo.type)) {
        const mensajeError = document.getElementById('mensajeError');
        mensajeError.classList.remove('d-none');
        mensajeError.textContent = 'Por favor sube una imagen válida (JPG, PNG o GIF)';
        return;
    }
    
    // Validar tamaño (5MB máximo)
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (archivo.size > maxSize) {
        const mensajeError = document.getElementById('mensajeError');
        mensajeError.classList.remove('d-none');
        mensajeError.textContent = 'El archivo no debe exceder 5MB';
        return;
    }
    
    // Crear FormData con todos los datos
    const formData = new FormData();
    
    // Agregar datos de texto
    formData.append('nombre', document.getElementById('nombre').value.trim());
    formData.append('documento', document.getElementById('documento').value.trim());
    formData.append('fecha_nacimiento', document.getElementById('fecha_nacimiento').value);
    formData.append('email', document.getElementById('email').value.trim());
    formData.append('telefono', document.getElementById('telefono').value.trim());
    formData.append('fecha_afiliacion', document.getElementById('fecha_afiliacion').value);
    formData.append('estado', document.getElementById('estado').value);
    formData.append('direccion', document.getElementById('direccion').value.trim());
    formData.append('entidad_salud', document.getElementById('entidad_salud').value.trim());
    formData.append('observaciones', document.getElementById('observaciones').value.trim());
    
    // Agregar archivo
    formData.append('foto_documento', archivo);
    
    // Validar campos requeridos
    if (!document.getElementById('nombre').value.trim() || !document.getElementById('documento').value.trim() || 
        !document.getElementById('email').value.trim() || !document.getElementById('telefono').value.trim() || 
        !document.getElementById('fecha_afiliacion').value) {
        const mensajeError = document.getElementById('mensajeError');
        mensajeError.classList.remove('d-none');
        mensajeError.textContent = 'Por favor completa todos los campos requeridos';
        return;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
    
    fetch('/corve/controllers/socio_controller.php', {
        method: 'POST',
        body: formData  // Enviar FormData sin Content-Type
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta:', data);
        
        if (data.success) {
            const mensajeExito = document.getElementById('mensajeExito');
            mensajeExito.classList.remove('d-none');
            mensajeExito.textContent = data.message || 'Socio creado correctamente';
            
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const mensajeError = document.getElementById('mensajeError');
        mensajeError.classList.remove('d-none');
        mensajeError.textContent = error.message;
        
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

/**
 * Guardar cambios de socio
 */
function guardarCambiosSocio() {
    const socioId = document.getElementById('socio_id').value;
    
    if (!socioId) {
        alert('Error: No se encontró el ID del socio');
        return;
    }
    
    // Obtener datos del formulario
    const data = {
        nombre: document.getElementById('edit_nombre').value.trim(),
        documento: document.getElementById('edit_documento').value.trim(),
        fecha_nacimiento: document.getElementById('edit_fecha_nacimiento').value,
        email: document.getElementById('edit_email').value.trim(),
        telefono: document.getElementById('edit_telefono').value.trim(),
        fecha_afiliacion: document.getElementById('edit_fecha_afiliacion').value,
        estado: document.getElementById('edit_estado').value,
        direccion: document.getElementById('edit_direccion').value.trim(),
        entidad_salud: document.getElementById('edit_entidad_salud').value.trim(),
        observaciones: document.getElementById('edit_observaciones').value.trim(),
        id: socioId,
        action: 'update'
    };
    
    // Validar campos requeridos
    if (!data.nombre || !data.documento || !data.email || !data.telefono) {
        alert('Por favor completa todos los campos requeridos');
        return;
    }
    
    const fotoInput = document.getElementById('edit_foto_documento');
    const tieneNuevaFoto = fotoInput.files && fotoInput.files.length > 0;
    
    // Si hay foto nueva, validar
    if (tieneNuevaFoto) {
        const archivo = fotoInput.files[0];
        
        // Validar tipo de archivo
        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
        if (!tiposPermitidos.includes(archivo.type)) {
            alert('❌ Tipo de archivo no permitido. Solo se permiten JPG, PNG y GIF');
            return;
        }
        
        // Validar tamaño
        const maxTamaño = 5 * 1024 * 1024; // 5MB
        if (archivo.size > maxTamaño) {
            alert('❌ El archivo es muy grande. Máximo 5 MB');
            return;
        }
    }
    
    const submitBtn = document.getElementById('btnGuardarCambios');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
    
    // Si hay foto nueva, usar FormData; si no, usar JSON
    if (tieneNuevaFoto) {
        const formData = new FormData();
        formData.append('id', socioId);
        formData.append('action', 'update');
        formData.append('nombre', data.nombre);
        formData.append('documento', data.documento);
        formData.append('fecha_nacimiento', data.fecha_nacimiento);
        formData.append('email', data.email);
        formData.append('telefono', data.telefono);
        formData.append('fecha_afiliacion', data.fecha_afiliacion);
        formData.append('estado', data.estado);
        formData.append('direccion', data.direccion);
        formData.append('entidad_salud', data.entidad_salud);
        formData.append('observaciones', data.observaciones);
        formData.append('foto_documento', fotoInput.files[0]);
        
        fetch('/corve/controllers/socio_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                // Leer la respuesta de error
                return response.json().then(data => {
                    throw new Error(data.message || `Error HTTP ${response.status}`);
                }).catch(() => {
                    throw new Error(`HTTP ${response.status}`);
                });
            }
            return response.json();
        })
        .then(result => {
            console.log('Respuesta:', result);
            
            if (result.success) {
                alert(result.message || 'Socio actualizado correctamente');
                location.reload();
            } else {
                throw new Error(result.message || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const mensajeError = document.getElementById('mensajeErrorEditar');
            mensajeError.classList.remove('d-none');
            mensajeError.textContent = error.message;
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    } else {
        // Sin foto nueva, usar JSON
        fetch('/corve/controllers/socio_controller.php?id=' + socioId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                // Leer la respuesta de error
                return response.json().then(errData => {
                    throw new Error(errData.message || `Error HTTP ${response.status}`);
                }).catch(() => {
                    throw new Error(`HTTP ${response.status}`);
                });
            }
            return response.json();
        })
        .then(result => {
            console.log('Respuesta:', result);
            
            if (result.success) {
                alert(result.message || 'Socio actualizado correctamente');
                location.reload();
            } else {
                throw new Error(result.message || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const mensajeError = document.getElementById('mensajeErrorEditar');
            mensajeError.classList.remove('d-none');
            mensajeError.textContent = error.message;
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
}

/**
 * Eliminar un socio
 */
function eliminarSocio(id) {
    const btn = document.querySelector(`button[data-id="${id}"].btn-eliminar`);
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    
    fetch('/corve/controllers/socio_controller.php?id=' + id, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta:', data);
        
        if (data.success) {
            alert(data.message || 'Socio eliminado correctamente');
            location.reload();
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar el socio: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// Ver detalles del socio en modal
function verSocioDetalles(id) {
    console.log('Cargando detalles del socio:', id);

    fetch(`/corve/controllers/socio_controller.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos del socio:', data);

            if (data.success && data.data) {
                const socio = data.data;

                // Llenar los campos del modal con los datos del socio
                document.getElementById('verNombre').textContent = socio.nombre || '-';
                document.getElementById('verDocumento').textContent = socio.documento || '-';
                document.getElementById('verEmail').textContent = socio.email || '-';
                document.getElementById('verTelefono').textContent = socio.telefono || '-';
                document.getElementById('verFechaNacimiento').textContent = formatearFecha(socio.fecha_nacimiento) || '-';
                document.getElementById('verDireccion').textContent = socio.direccion || '-';
                document.getElementById('verFechaAfiliacion').textContent = formatearFecha(socio.fecha_afiliacion) || '-';
                document.getElementById('verEstado').textContent = capitalizarPrimera(socio.estado) || '-';
                document.getElementById('verEntidadSalud').textContent = socio.entidad_salud || '-';
                document.getElementById('verSaldo').textContent = formatearMoneda(socio.saldo) || '$0';
                document.getElementById('verObservaciones').textContent = socio.observaciones || '-';

                // Manejar la foto del documento
                const fotoDocumento = document.getElementById('fotoDocumento');
                const sinFoto = document.getElementById('sinFoto');

                if (socio.documento_pdf && socio.documento_pdf.trim() !== '') {
                    fotoDocumento.src = '/corve/' + socio.documento_pdf;
                    fotoDocumento.style.display = 'block';
                    sinFoto.style.display = 'none';
                    console.log('Foto cargada:', fotoDocumento.src);
                } else {
                    fotoDocumento.style.display = 'none';
                    sinFoto.style.display = 'block';
                    console.log('No hay foto del documento');
                }

                // Guardar el ID para el botón de editar
                document.getElementById('btnEditarDesdeVer').dataset.id = id;

                // Mostrar el modal
                const modal = new bootstrap.Modal(document.getElementById('verSocioModal'));
                modal.show();
            } else {
                throw new Error(data.message || 'No se encontraron datos del socio');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los detalles del socio: ' + error.message);
        });
}

// Función auxiliar para formatear fechas
function formatearFecha(fecha) {
    if (!fecha) return '-';
    const date = new Date(fecha);
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
}

// Función auxiliar para capitalizar primera letra
function capitalizarPrimera(texto) {
    if (!texto) return '';
    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

// Función auxiliar para formatear moneda
function formatearMoneda(monto) {
    if (monto === null || monto === undefined) return '$0';
    return '$' + parseFloat(monto).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

console.log('Funcionalidades de socios cargadas correctamente');
