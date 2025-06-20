<?php
header('Content-Type: application/json; charset=utf-8');

// Incluir archivos necesarios
require_once 'includes/database/Database.php';
require_once 'models/Socio.php';
require_once 'controllers/socio_controller.php';

// Función para enviar respuestas JSON estandarizadas
function sendJsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Método no permitido. Se esperaba una petición POST.', null, 405);
}

// Obtener y validar los datos de la petición
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse(false, 'Error en el formato JSON: ' . json_last_error_msg(), null, 400);
}

// Validar parámetros requeridos
if (empty($input['accion']) || empty($input['socio_id'])) {
    sendJsonResponse(false, 'Faltan parámetros requeridos: accion, socio_id', null, 400);
}

// Validar formato del ID
if (!is_numeric($input['socio_id'])) {
    sendJsonResponse(false, 'El ID del socio debe ser un número', null, 400);
}

$accion = strtolower(trim($input['accion']));
$socioId = (int)$input['socio_id'];
$motivo = trim($input['motivo'] ?? '');

// Validar que la acción sea válida
$accionesPermitidas = ['activar', 'lesionar', 'retirar'];
if (!in_array($accion, $accionesPermitidas)) {
    sendJsonResponse(
        false, 
        'Acción no válida. Las acciones permitidas son: ' . implode(', ', $accionesPermitidas),
        null,
        400
    );
}

try {
    // Inicializar controlador y modelo
    $controller = new SocioController();
    $socio = new Socio();
    $socio->id = $socioId;
    
    // Verificar que el socio existe
    if (!$socio->readOne()) {
        throw new Exception("No se encontró el socio con ID: $socioId", 404);
    }
    
    // Determinar el nuevo estado según la acción
    $estados = [
        'activar' => Socio::ESTADO_ACTIVO,
        'lesionar' => Socio::ESTADO_LESIONADO,
        'retirar' => Socio::ESTADO_RETIRADO
    ];
    
    $mensajes = [
        'activar' => 'Socio reactivado correctamente',
        'lesionar' => 'Socio marcado como lesionado correctamente',
        'retirar' => 'Socio retirado correctamente'
    ];
    
    $nuevoEstado = $estados[$accion] ?? '';
    $mensaje = $mensajes[$accion] ?? 'Operación completada';
    
    // Validar que se pudo determinar el estado
    if (empty($nuevoEstado)) {
        throw new Exception('No se pudo determinar el nuevo estado para la acción solicitada');
    }
    
    // Cambiar el estado del socio
    if ($controller->cambiarEstado($socioId, $nuevoEstado, $motivo)) {
        // Registrar el cambio en el historial
        if (registrarCambioEstado($socioId, $nuevoEstado, $motivo)) {
            // Devolver respuesta exitosa
            sendJsonResponse(
                true,
                $mensaje,
                [
                    'socio_id' => $socioId,
                    'nuevo_estado' => $nuevoEstado,
                    'fecha_cambio' => date('Y-m-d H:i:s'),
                    'motivo' => $motivo
                ]
            );
        } else {
            // Log del error pero no fallar la operación principal
            error_log("No se pudo registrar el cambio de estado para el socio $socioId");
            sendJsonResponse(
                true,
                "$mensaje (No se pudo registrar en el historial)",
                [
                    'socio_id' => $socioId,
                    'nuevo_estado' => $nuevoEstado,
                    'fecha_cambio' => date('Y-m-d H:i:s'),
                    'advertencia' => 'No se pudo registrar el cambio en el historial'
                ]
            );
        }
    } else {
        throw new Exception('No se pudo actualizar el estado del socio');
    }
    
} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    $message = $e->getCode() === 404 ? $e->getMessage() : 'Error al procesar la solicitud';
    
    // Log del error para depuración
    error_log("Error en acciones_socio.php: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    sendJsonResponse(false, $message, null, $statusCode);
}

// Función para registrar cambios de estado
function registrarCambioEstado($socioId, $nuevoEstado, $motivo = '') {
    $query = "INSERT INTO historial_estados_socios 
              (socio_id, estado_anterior, estado_nuevo, motivo, fecha_cambio)
              VALUES (:socio_id, :estado_anterior, :estado_nuevo, :motivo, NOW())";
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Verificar si la tabla existe, si no, crearla
        $conn->exec("CREATE TABLE IF NOT EXISTS historial_estados_socios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            socio_id INT NOT NULL,
            estado_anterior VARCHAR(20) NOT NULL,
            estado_nuevo VARCHAR(20) NOT NULL,
            motivo TEXT,
            fecha_cambio DATETIME NOT NULL,
            INDEX (socio_id),
            INDEX (fecha_cambio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $stmt = $conn->prepare($query);
        
        // Obtener el estado actual del socio para registrarlo como estado anterior
        $socio = new Socio();
        $socio->id = $socioId;
        $estadoAnterior = 'desconocido';
        
        if ($socio->readOne()) {
            $estadoAnterior = $socio->estado;
        }
        
        $stmt->bindParam(":socio_id", $socioId, PDO::PARAM_INT);
        $stmt->bindParam(":estado_anterior", $estadoAnterior);
        $stmt->bindParam(":estado_nuevo", $nuevoEstado);
        $stmt->bindParam(":motivo", $motivo);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error al registrar cambio de estado: " . $e->getMessage());
        return false;
    }
}
?>
