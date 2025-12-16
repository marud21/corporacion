<?php
// Configuración para que los errores no se muestren en la respuesta JSON, solo se logueen
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_error.log');

/**
 * Controlador para gestionar las operaciones CRUD de los socios
 */

// Configurar cabeceras CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Incluir el modelo Socio y la conexión a la base de datos
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../includes/database/Database.php';

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar que el directorio de logs exista y sea escribible
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
if (!is_writable($logDir)) {
    chmod($logDir, 0777);
}

/**
 * Procesar carga de archivo de documento
 * @param string $documento Número de documento del socio
 * @return string|null Ruta del archivo guardado o null si es actualización sin foto
 */
function procesarFotoDocumento($documento, $esActualizacion = false) {
    // Si es actualización y no hay archivo, retornar null
    if ($esActualizacion && (!isset($_FILES['foto_documento']) || $_FILES['foto_documento']['error'] === UPLOAD_ERR_NO_FILE)) {
        error_log('[UPLOAD] Actualización sin foto, retornando null');
        return null;
    }
    
    // Validar que exista el archivo
    if (!isset($_FILES['foto_documento'])) {
        throw new Exception('No se recibió archivo de foto', 400);
    }
    
    $archivo = $_FILES['foto_documento'];
    
    // Validar errores de carga
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $mensajeError = 'Error desconocido al cargar archivo';
        switch ($archivo['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $mensajeError = 'Archivo excede el límite de php.ini';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $mensajeError = 'Archivo excede el límite del formulario';
                break;
            case UPLOAD_ERR_PARTIAL:
                $mensajeError = 'El archivo se cargó parcialmente';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $mensajeError = 'No hay directorio temporal en el servidor';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $mensajeError = 'No se puede escribir en el disco';
                break;
            case UPLOAD_ERR_EXTENSION:
                $mensajeError = 'Extensión de archivo bloqueada por PHP';
                break;
        }
        throw new Exception($mensajeError, 400);
    }
    
    error_log('[UPLOAD] Archivo recibido: ' . $archivo['name'] . ', Tamaño: ' . $archivo['size'] . ', Tmp: ' . $archivo['tmp_name']);
    
    // Verificar que el archivo temporal exista
    if (!file_exists($archivo['tmp_name'])) {
        error_log('[UPLOAD] ERROR: Archivo temporal no existe: ' . $archivo['tmp_name']);
        throw new Exception('El archivo temporal se perdió. Intenta nuevamente', 500);
    }
    
    // Validar extensión
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $extensionesPermitidas)) {
        throw new Exception('Tipo de archivo no permitido. Solo JPG, PNG o GIF', 400);
    }
    
    error_log('[UPLOAD] Extensión válida: ' . $extension);
    
    // Validar tamaño (5MB máximo)
    $maxSize = 5 * 1024 * 1024;
    if ($archivo['size'] > $maxSize) {
        throw new Exception('El archivo excede el tamaño máximo permitido (5MB)', 400);
    }
    
    // Crear directorio si no existe
    $uploadDir = __DIR__ . '/../uploads/documentos';
    
    if (!is_dir($uploadDir)) {
        error_log('[UPLOAD] Creando directorio: ' . $uploadDir);
        if (!@mkdir($uploadDir, 0777, true)) {
            throw new Exception('No se pudo crear el directorio de uploads', 500);
        }
    }
    
    // Asegurar permisos
    @chmod($uploadDir, 0777);
    
    if (!is_writable($uploadDir)) {
        error_log('[UPLOAD] ERROR: Directorio no escribible: ' . $uploadDir);
        throw new Exception('El directorio de uploads no tiene permisos de escritura', 500);
    }
    
    error_log('[UPLOAD] Directorio listo: ' . $uploadDir);
    
    // Generar nombre único
    $nombreArchivo = 'doc_' . $documento . '_' . time() . '_' . uniqid() . '.' . $extension;
    $rutaDestino = $uploadDir . '/' . $nombreArchivo;
    
    error_log('[UPLOAD] Destino: ' . $rutaDestino);
    
    // Intentar mover/copiar archivo
    $exito = false;
    $tmpExistiaAntes = file_exists($archivo['tmp_name']);
    
    // Primero intentar move_uploaded_file (si es archivo HTTP)
    if (is_uploaded_file($archivo['tmp_name'])) {
        error_log('[UPLOAD] Usando move_uploaded_file');
        $exito = @move_uploaded_file($archivo['tmp_name'], $rutaDestino);
        if ($exito) {
            error_log('[UPLOAD] move_uploaded_file éxito');
        } else {
            error_log('[UPLOAD] move_uploaded_file falló');
        }
    }
    
    // Si falla, usar copy (solo si el archivo temporal aún existe)
    if (!$exito && $tmpExistiaAntes && file_exists($archivo['tmp_name'])) {
        error_log('[UPLOAD] Intentando copy');
        $exito = @copy($archivo['tmp_name'], $rutaDestino);
        if ($exito) {
            error_log('[UPLOAD] Copy éxito, limpiando temporal');
            @unlink($archivo['tmp_name']);
        } else {
            error_log('[UPLOAD] Copy falló');
        }
    }
    
    if (!$exito) {
        error_log('[UPLOAD] ERROR: No se pudo guardar archivo.');
        throw new Exception('Error al guardar el archivo en el servidor', 500);
    }
    
    // Verificar que se guardó (solo verificar destino, no el temporal que ya fue movido)
    if (!file_exists($rutaDestino)) {
        error_log('[UPLOAD] ERROR: Archivo no existe después de guardar: ' . $rutaDestino);
        throw new Exception('El archivo no se guardó correctamente', 500);
    }
    
    $rutaRelativa = 'uploads/documentos/' . $nombreArchivo;
    error_log('[UPLOAD] ÉXITO: Archivo guardado en ' . $rutaRelativa . ', tamaño: ' . filesize($rutaDestino));
    
    return $rutaRelativa;
}

class SocioController {
    private $socioModel;
    private $database;

    public function __construct() {
        $this->socioModel = new Socio();
        $this->database = new Database();
    }

    /**
     * Listar todos los socios con paginación
     * @param int $pagina Número de página (opcional)
     * @param int $porPagina Cantidad de registros por página (opcional)
     * @return array Datos de los socios con metadatos de paginación
     */
    public function index($pagina = 1, $porPagina = 10) {
        try {
            $conn = $this->database->getConnection();
            
            // Contar el total de registros
            $queryTotal = "SELECT COUNT(*) as total FROM usuarios u 
                          INNER JOIN socios s ON u.id = s.id 
                          WHERE u.rol = 'socio' AND u.activo = 1";
            $stmtTotal = $conn->query($queryTotal);
            $totalRegistros = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Calcular páginas totales
            $totalPaginas = ceil($totalRegistros / $porPagina);
            $offset = ($pagina - 1) * $porPagina;
            
            // Obtener los registros de la página actual
            $query = "SELECT u.*, s.entidad_salud, s.afiliado, s.saldo, s.documento_pdf, 
                             s.estado, s.fecha_estado, s.motivo_estado
                      FROM usuarios u 
                      INNER JOIN socios s ON u.id = s.id 
                      WHERE u.rol = 'socio' AND u.activo = 1
                      ORDER BY u.nombre ASC
                      LIMIT :offset, :limit";
            
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$porPagina, PDO::PARAM_INT);
            $stmt->execute();
            
            $socios = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $socios[] = $this->formatearDatosSocio($row);
            }
            
            return [
                'data' => $socios,
                'paginacion' => [
                    'total' => (int)$totalRegistros,
                    'por_pagina' => (int)$porPagina,
                    'pagina_actual' => (int)$pagina,
                    'total_paginas' => (int)$totalPaginas,
                    'hay_mas' => $pagina < $totalPaginas
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en SocioController->index(): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener un socio por su ID
     * @param int $id ID del socio
     * @return array Datos del socio
     * @throws Exception Si el socio no existe
     */
    public function show($id) {
        try {
            $this->socioModel->id = $id;
            
            if(!$this->socioModel->readOne()) {
                throw new Exception("No se encontró el socio con ID: $id", 404);
            }
            
            // Obtener datos adicionales si es necesario
            $conn = $this->database->getConnection();
            $query = "SELECT * FROM socios WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $datosSocio = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$datosSocio) {
                throw new Exception("No se encontraron datos adicionales para el socio con ID: $id", 404);
            }
            
            // Combinar datos de usuario y socio
            $socio = [
                'id' => $this->socioModel->id,
                'nombre' => $this->socioModel->nombre,
                'documento' => $this->socioModel->documento,
                'email' => $this->socioModel->email,
                'telefono' => $this->socioModel->telefono,
                'direccion' => $this->socioModel->direccion,
                'fecha_nacimiento' => $this->socioModel->fecha_nacimiento,
                'fecha_afiliacion' => $datosSocio['fecha_afiliacion'] ?? null,
                'entidad_salud' => $datosSocio['entidad_salud'] ?? null,
                'documento_pdf' => $datosSocio['documento_pdf'] ?? null,
                'afiliado' => (bool)($datosSocio['afiliado'] ?? false),
                'saldo' => (float)($datosSocio['saldo'] ?? 0),
                'estado' => $datosSocio['estado'] ?? 'activo',
                'fecha_estado' => $datosSocio['fecha_estado'] ?? null,
                'motivo_estado' => $datosSocio['motivo_estado'] ?? null
            ];
            
            return $socio;
            
        } catch (Exception $e) {
            error_log("Error en SocioController->show(): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear un nuevo socio
     * @param array $data Datos del socio a crear
     * @return array Datos del socio creado
     * @throws Exception Si hay un error al crear el socio
     */
    public function store($data) {
        $conn = null;
        
        try {
            $conn = $this->database->getConnection();
            
            // Validar datos requeridos
            $camposRequeridos = ['nombre', 'documento', 'email', 'telefono'];
            foreach ($camposRequeridos as $campo) {
                if (empty(trim($data[$campo] ?? ''))) {
                    throw new Exception("El campo $campo es obligatorio", 400);
                }
            }
            
            // Procesar archivo de documento (OBLIGATORIO)
            $documento_pdf = null;
            
            // Si ya fue procesada en el controlador (desde POST FormData), usar ese valor
            if (!empty($data['documento_pdf'])) {
                $documento_pdf = $data['documento_pdf'];
                error_log('[STORE] Usando documento_pdf ya procesado: ' . $documento_pdf);
            } elseif (isset($_FILES['foto_documento'])) {
                // Si no está procesada pero existe el archivo, procesarla ahora
                $documento_pdf = procesarFotoDocumento($data['documento']);
                error_log('[STORE] Procesando documento_pdf nuevo');
            } else {
                throw new Exception("La foto del documento de identidad es obligatoria", 400);
            }
            
            // Validar documento único
            $queryCheck = "SELECT id FROM usuarios WHERE documento = :documento";
            $stmtCheck = $conn->prepare($queryCheck);
            $stmtCheck->bindParam(':documento', $data['documento']);
            $stmtCheck->execute();
            
            if ($stmtCheck->rowCount() > 0) {
                throw new Exception("Ya existe un usuario con este documento", 409);
            }
            
            // Iniciar transacción
            $conn->beginTransaction();
            
            // 1. Crear el usuario
            $query = "INSERT INTO usuarios 
                     (nombre, documento, email, telefono, direccion, fecha_nacimiento, rol, activo, password_hash) 
                     VALUES 
                     (:nombre, :documento, :email, :telefono, :direccion, :fecha_nacimiento, 'socio', 1, :password_hash)";
            
            $stmt = $conn->prepare($query);
            
            // Hashear la contraseña (usando el documento como contraseña por defecto)
            $password_hash = password_hash($data['documento'], PASSWORD_BCRYPT);
            
            // Limpiar y validar datos
            $nombre = trim($data['nombre']);
            $documento = trim($data['documento']);
            $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
            $telefono = trim($data['telefono']);
            $direccion = trim($data['direccion'] ?? '');
            $fecha_nacimiento = !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null;
            
            if (!$email) {
                throw new Exception("El formato del correo electrónico no es válido", 400);
            }
            
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":documento", $documento);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":telefono", $telefono);
            $stmt->bindParam(":direccion", $direccion);
            $stmt->bindParam(":fecha_nacimiento", $fecha_nacimiento);
            $stmt->bindParam(":password_hash", $password_hash);
            
            if(!$stmt->execute()) {
                throw new Exception("Error al crear el usuario: " . implode(", ", $stmt->errorInfo()));
            }
            
            // Obtener el ID del usuario recién creado
            $usuario_id = $conn->lastInsertId();
            
            // 2. Crear el registro en la tabla socios
            $query_socio = "INSERT INTO socios 
                          (id, entidad_salud, documento_pdf, afiliado, saldo, fecha_afiliacion) 
                          VALUES 
                          (:id, :entidad_salud, :documento_pdf, :afiliado, :saldo, :fecha_afiliacion)";
            
            $stmt_socio = $conn->prepare($query_socio);
            
            $entidad_salud = trim($data['entidad_salud'] ?? '');
            $afiliado = isset($data['afiliado']) ? 1 : 0;
            $fecha_afiliacion = $data['fecha_afiliacion'] ?? date('Y-m-d');

            // Calcular el valor de la mensualidad proporcional según la fecha de afiliación
            $primerMonto = 45000;
            $diaAfiliacion = (int)date('j', strtotime($fecha_afiliacion));
            if ($diaAfiliacion <= 7) {
                $primerMonto = 45000;
            } elseif ($diaAfiliacion <= 21) {
                $primerMonto = 22500;
            } else {
                $primerMonto = 11250;
            }
            // Sumar afiliación, inscripción y mensualidad proporcional al saldo inicial
            $cuota_afiliacion = isset($data['cuota_afiliacion']) ? floatval($data['cuota_afiliacion']) : 100000;
            $cuota_inscripcion = isset($data['cuota_inscripcion']) ? floatval($data['cuota_inscripcion']) : 45000;
            $saldo = $cuota_afiliacion + $cuota_inscripcion + $primerMonto;

            $stmt_socio->bindParam(":id", $usuario_id);
            $stmt_socio->bindParam(":entidad_salud", $entidad_salud);
            $stmt_socio->bindParam(":documento_pdf", $documento_pdf);
            $stmt_socio->bindParam(":afiliado", $afiliado, PDO::PARAM_INT);
            $stmt_socio->bindParam(":saldo", $saldo);
            $stmt_socio->bindParam(":fecha_afiliacion", $fecha_afiliacion);

            if(!$stmt_socio->execute()) {
                throw new Exception("Error al crear el registro del socio: " . implode(", ", $stmt_socio->errorInfo()));
            }

            // Si todo salió bien, confirmar la transacción
            $conn->commit();

            // Eliminar el registro de pago inicial: NO insertar ningún pago en la tabla pagos
            // El saldo inicial ya incluye la mensualidad proporcional

            return $usuario_id;
            
        } catch (PDOException $e) {
            // Log de depuración para errores de SQL
            error_log('[ERROR SQL] ' . $e->getMessage());
            // Si hubo algún error, revertir la transacción
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            
            error_log("Error en SocioController::store: " . $e->getMessage());
            throw $e; // Relanzar la excepción para manejarla en el controlador principal
        } catch (Exception $e) {
            // Log de depuración para errores generales
            error_log('[ERROR GENERAL] ' . $e->getMessage());
            // Si hubo algún error, revertir la transacción
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            
            error_log("Error en SocioController::store: " . $e->getMessage());
            throw $e; // Relanzar la excepción para manejarla en el controlador principal
        }
    }

    /**
     * Actualizar un socio existente
     * @param int $id ID del socio a actualizar
     * @param array $data Nuevos datos del socio
     * @return bool True si la actualización fue exitosa
     * @throws Exception Si hay un error al actualizar
     */
    public function update($id, $data) {
        $conn = null;
        
        try {
            $conn = $this->database->getConnection();
            
            // Verificar que el socio existe
            $this->socioModel->id = $id;
            if (!$this->socioModel->readOne()) {
                throw new Exception("No se encontró el socio con ID: $id", 404);
            }
            
            // Validar documento único (si se está actualizando y el documento cambió)
            if (!empty($data['documento'])) {
                // Obtener el documento actual del socio
                $currentDoc = $this->socioModel->documento;
                
                // Solo validar si el documento es diferente al actual
                if ($data['documento'] !== $currentDoc) {
                    $queryCheck = "SELECT id FROM usuarios WHERE documento = :documento AND id != :id";
                    $stmtCheck = $conn->prepare($queryCheck);
                    $stmtCheck->bindParam(':documento', $data['documento']);
                    $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmtCheck->execute();
                    
                    if ($stmtCheck->rowCount() > 0) {
                        throw new Exception("Ya existe otro usuario con este documento", 409);
                    }
                }
            }
            
            // Iniciar transacción
            $conn->beginTransaction();
            
            // 1. Actualizar el usuario
            $query = "UPDATE usuarios 
                     SET nombre = :nombre, documento = :documento, email = :email, 
                         telefono = :telefono, direccion = :direccion, fecha_nacimiento = :fecha_nacimiento
                     WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            
            // Limpiar y validar datos
            $nombre = trim($data['nombre'] ?? $this->socioModel->nombre);
            $documento = trim($data['documento'] ?? $this->socioModel->documento);
            $email = filter_var(trim($data['email'] ?? $this->socioModel->email), FILTER_VALIDATE_EMAIL);
            $telefono = trim($data['telefono'] ?? $this->socioModel->telefono);
            $direccion = trim($data['direccion'] ?? $this->socioModel->direccion ?? '');
            $fecha_nacimiento = !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : $this->socioModel->fecha_nacimiento;
            
            if (!$email) {
                throw new Exception("El formato del correo electrónico no es válido", 400);
            }
            
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":documento", $documento);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":telefono", $telefono);
            $stmt->bindParam(":direccion", $direccion);
            $stmt->bindParam(":fecha_nacimiento", $fecha_nacimiento);
            $stmt->bindParam(":id", $id);
            
            if(!$stmt->execute()) {
                throw new Exception("Error al actualizar el usuario");
            }
            
            // 2. Actualizar los datos específicos del socio
            $this->socioModel->id = $id;
            $this->socioModel->entidad_salud = $data['entidad_salud'] ?? null;
            
            // Solo actualizar documento_pdf si viene en los datos (no es null implícitamente)
            if (array_key_exists('documento_pdf', $data)) {
                $this->socioModel->documento_pdf = $data['documento_pdf'];
            }
            
            $this->socioModel->afiliado = isset($data['afiliado']) ? 1 : 0;
            $this->socioModel->saldo = floatval($data['saldo'] ?? 0);
            $this->socioModel->fecha_afiliacion = $data['fecha_afiliacion'] ?? null;
            
            // Actualizar estado si se proporciona
            $actualizarEstado = false;
            if (!empty($data['estado'])) {
                $this->socioModel->estado = $data['estado'];
                $this->socioModel->fecha_estado = date('Y-m-d H:i:s');
                $this->socioModel->motivo_estado = $data['motivo_estado'] ?? null;
                $actualizarEstado = true;
            }
            
            if(!$this->socioModel->update($actualizarEstado)) {
                throw new Exception("Error al actualizar los datos del socio");
            }
            
            // Confirmar la transacción
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            error_log("Error en SocioController::update: " . $e->getMessage());
            throw $e; // Relanzar la excepción para manejarla en el controlador principal
        }
    }

    /**
     * Eliminar un socio (borrado lógico)
     * @param int $id ID del socio a eliminar
     * @return bool True si la eliminación fue exitosa
     * @throws Exception Si hay un error al eliminar
     */
    public function delete($id) {
        $conn = null;
        
        try {
            $conn = $this->database->getConnection();
            
            // Verificar que el socio existe
            $this->socioModel->id = $id;
            if (!$this->socioModel->readOne()) {
                throw new Exception("No se encontró el socio con ID: $id", 404);
            }
            
            // Iniciar transacción
            $conn->beginTransaction();
            
            // 1. Realizar borrado lógico en usuarios (no eliminamos físicamente)
            $query = "UPDATE usuarios SET activo = 0 WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // 2. Cambiar estado a retirado en socios
            $queryUpdate = "UPDATE socios SET estado = 'retirado', fecha_estado = NOW() WHERE id = :id";
            $stmtUpdate = $conn->prepare($queryUpdate);
            $stmtUpdate->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtUpdate->execute();
            
            $conn->commit();
            
            // Registrar la acción
            $this->registrarAccion($id, 'baja', 'Socio dado de baja del sistema');
            
            return true;
            
        } catch (Exception $e) {
            if ($conn !== null) {
                $conn->rollBack();
            }
            error_log("Error en SocioController->delete(): " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Formatear los datos del socio para la respuesta
     * @param array $row Fila de la base de datos
     * @return array Datos formateados
     */
    private function formatearDatosSocio($row) {
        return [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'documento' => $row['documento'],
            'email' => $row['email'],
            'telefono' => $row['telefono'],
            'direccion' => $row['direccion'] ?? null,
            'fecha_nacimiento' => $row['fecha_nacimiento'] ?? null,
            'entidad_salud' => $row['entidad_salud'] ?? null,
            'documento_pdf' => $row['documento_pdf'] ?? null,
            'afiliado' => (bool)($row['afiliado'] ?? false),
            'saldo' => (float)($row['saldo'] ?? 0),
            'estado' => $row['estado'] ?? 'activo',
            'fecha_estado' => $row['fecha_estado'] ?? null,
            'motivo_estado' => $row['motivo_estado'] ?? null
        ];
    }
    
    /**
     * Registrar una acción realizada sobre un socio
     * @param int $socioId ID del socio
     * @param string $accion Tipo de acción realizada
     * @param string $descripcion Descripción detallada
     * @return bool True si se registró correctamente
     */
    private function registrarAccion($socioId, $accion, $descripcion) {
        try {
            $conn = $this->database->getConnection();
            $query = "INSERT INTO historial_acciones_socios 
                     (socio_id, accion, descripcion, fecha, ip, agente_usuario, usuario_id)
                     VALUES 
                     (:socio_id, :accion, :descripcion, NOW(), :ip, :agente_usuario, :usuario_id)";
            
            $stmt = $conn->prepare($query);
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $agente = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            
            $stmt->bindParam(':socio_id', $socioId, PDO::PARAM_INT);
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':agente_usuario', $agente);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Error al registrar acción de socio: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Enviar una respuesta JSON estandarizada
 * 
 * @param bool $success Indica si la operación fue exitosa
 * @param string $message Mensaje descriptivo del resultado
 * @param mixed $data Datos adicionales a incluir en la respuesta (opcional)
 * @param int $statusCode Código de estado HTTP (por defecto 200)
 */
function sendJsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    // En modo desarrollo, incluir información de depuración
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $response['debug'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage() / 1024 / 1024 . 'MB'
        ];
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Manejo de las peticiones
try {
    // Configuración inicial
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Obtener el ID del query string
    $id = null;
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $id = (int)$_GET['id'];
    }
    
    // Crear instancia del controlador
    $controller = new SocioController();
    
    // Enrutamiento
    switch ($method) {
        case 'GET':
            if ($id !== null) {
                // Obtener un socio específico
                $socio = $controller->show($id);
                sendJsonResponse(true, 'Socio obtenido correctamente', $socio);
            } else {
                // Listar socios con paginación
                $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
                $porPagina = isset($_GET['por_pagina']) ? max(1, min(100, (int)$_GET['por_pagina'])) : 10;
                
                $resultado = $controller->index($pagina, $porPagina);
                sendJsonResponse(true, 'Lista de socios obtenida correctamente', $resultado);
            }
            break;
            
        case 'POST':
            // Crear un nuevo socio o actualizar existente (si viene con FormData y foto)
            // Verificar si es FormData (contiene archivo) o JSON
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'multipart/form-data') !== false) {
                // Es FormData (contiene archivo)
                $data = $_POST;
                error_log('[DEBUG] Datos recibidos en POST (FormData): ' . json_encode($data));
                
                // PROCESAR FOTO INMEDIATAMENTE si existe
                // Esto DEBE hacerse antes de que PHP limpie el archivo temporal
                if (!empty($_FILES['foto_documento']['tmp_name']) && $_FILES['foto_documento']['error'] === UPLOAD_ERR_OK) {
                    error_log('[DEBUG] Archivo detectado: ' . $_FILES['foto_documento']['tmp_name']);
                    
                    try {
                        $esActualizacion = !empty($data['action']) && $data['action'] === 'update';
                        $data['documento_pdf'] = procesarFotoDocumento($data['documento'] ?? '', $esActualizacion);
                        error_log('[DEBUG] Foto procesada: ' . $data['documento_pdf']);
                    } catch (Exception $e) {
                        error_log('[DEBUG] Error al procesar foto: ' . $e->getMessage());
                        throw $e;
                    }
                }
                
                // Verificar si es actualización o creación
                if (!empty($data['action']) && $data['action'] === 'update' && !empty($data['id'])) {
                    // Es una actualización con FormData
                    $socioId = (int)$data['id'];
                    $controller->update($socioId, $data);
                    sendJsonResponse(true, 'Socio actualizado correctamente');
                } else {
                    // Es creación de nuevo socio
                    $idNuevoSocio = $controller->store($data);
                    sendJsonResponse(true, 'Socio creado correctamente', ['id' => $idNuevoSocio], 201);
                }
            } else {
                // Es JSON
                error_log('[DEBUG] Datos recibidos en POST (JSON): ' . file_get_contents('php://input'));
                
                $jsonInput = file_get_contents('php://input');
                if (empty($jsonInput)) {
                    throw new Exception('No se recibieron datos para crear el socio', 400);
                }
                
                $data = json_decode($jsonInput, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Error en el formato JSON: ' . json_last_error_msg(), 400);
                }
                
                $idNuevoSocio = $controller->store($data);
                sendJsonResponse(true, 'Socio creado correctamente', ['id' => $idNuevoSocio], 201);
            }
            break;
            
        case 'PUT':
            // Actualizar un socio existente
            if ($id === null) {
                throw new Exception('Se requiere el ID del socio', 400);
            }
            
            // Verificar si es FormData o JSON
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'multipart/form-data') !== false) {
                // Es FormData (contiene archivo)
                $data = $_POST;
                
                // Procesar foto si existe
                if (!empty($_FILES['foto_documento']['tmp_name'])) {
                    $data['foto_documento'] = procesarFotoDocumento($_POST['documento'] ?? '', true);
                }
            } else {
                // Es JSON
                $jsonInput = file_get_contents('php://input');
                if (empty($jsonInput)) {
                    throw new Exception('No se recibieron datos para actualizar', 400);
                }
                
                $data = json_decode($jsonInput, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Error en el formato JSON: ' . json_last_error_msg(), 400);
                }
            }
            
            $controller->update($id, $data);
            sendJsonResponse(true, 'Socio actualizado correctamente');
            break;
            
        case 'DELETE':
            // Eliminar un socio (borrado lógico)
            if ($id === null) {
                throw new Exception('Se requiere el ID del socio', 400);
            }
            
            $controller->delete($id);
            sendJsonResponse(true, 'Socio eliminado correctamente');
            break;
            
        default:
            throw new Exception('Método no permitido', 405);
    }
    
} catch (Exception $e) {
    // Manejo de errores centralizado
    $statusCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    $message = $e->getMessage();
    
    // Log del error
    error_log(sprintf(
        '[%s] %s: %s en %s línea %s',
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    
    // Preparar respuesta de error
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    // En desarrollo, incluir más detalles del error
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $response['error'] = [
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace()
        ];
    }
    
    sendJsonResponse(false, $message, $response, $statusCode);
}
