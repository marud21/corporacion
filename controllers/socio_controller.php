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
            $documento_pdf = $data['documento_pdf'] ?? null;
            $afiliado = isset($data['afiliado']) ? 1 : 0;
            $saldo = floatval($data['saldo'] ?? 0);
            $fecha_afiliacion = $data['fecha_afiliacion'] ?? date('Y-m-d');
            
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
            
            // Validar documento único (si se está actualizando)
            if (!empty($data['documento'])) {
                $queryCheck = "SELECT id FROM usuarios WHERE documento = :documento AND id != :id";
                $stmtCheck = $conn->prepare($queryCheck);
                $stmtCheck->bindParam(':documento', $data['documento']);
                $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
                $stmtCheck->execute();
                
                if ($stmtCheck->rowCount() > 0) {
                    throw new Exception("Ya existe otro usuario con este documento", 409);
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
            
            // 2. Actualizar los datos específicos del socio (excepto el estado que se maneja aparte)
            $this->socioModel->id = $id;
            $this->socioModel->entidad_salud = $data['entidad_salud'] ?? null;
            $this->socioModel->documento_pdf = $data['documento_pdf'] ?? null;
            $this->socioModel->afiliado = isset($data['afiliado']) ? 1 : 0;
            $this->socioModel->saldo = floatval($data['saldo'] ?? 0);
            
            // No actualizar el estado aquí, se maneja aparte
            if(!$this->socioModel->update()) {
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
            
            // 2. Opcional: Podemos registrar la fecha de baja
            $queryUpdate = "UPDATE socios SET fecha_baja = NOW() WHERE id = :id";
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
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    // Obtener el ID si está presente (asumimos la ruta /api/socios[/:id])
    $id = null;
    if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
        $id = (int)$pathParts[2];
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
            // Crear un nuevo socio
            // Log de depuración para entrada de datos
            error_log('[DEBUG] Datos recibidos en POST: ' . file_get_contents('php://input'));
            
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
            break;
            
        case 'PUT':
            // Actualizar un socio existente
            if ($id === null) {
                throw new Exception('Se requiere el ID del socio', 400);
            }
            
            $jsonInput = file_get_contents('php://input');
            if (empty($jsonInput)) {
                throw new Exception('No se recibieron datos para actualizar', 400);
            }
            
            $data = json_decode($jsonInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error en el formato JSON: ' . json_last_error_msg(), 400);
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
