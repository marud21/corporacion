<?php
// Configurar cabeceras CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Incluir el modelo Socio
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../includes/database/Database.php';

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class SocioEstadoController {
    private $socioModel;
    private $database;

    public function __construct() {
        $this->socioModel = new Socio();
        $this->database = new Database();
    }

    /**
     * Cambia el estado de un socio
     * @param int $socioId ID del socio
     * @param string $nuevoEstado Nuevo estado (activo, lesionado, retirado)
     * @param string $motivo Motivo del cambio de estado
     * @return bool True si se actualizó correctamente
     */
    public function cambiarEstado($socioId, $nuevoEstado, $motivo = '') {
        try {
            $this->socioModel->id = $socioId;
            
            // Verificar que el socio existe
            if (!$this->socioModel->readOne()) {
                throw new Exception("No se encontró el socio con ID: $socioId", 404);
            }
            
            // Actualizar el estado del socio
            $conn = $this->database->getConnection();
            $query = "UPDATE socios SET estado = :estado, fecha_estado = NOW(), motivo_estado = :motivo WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':estado', $nuevoEstado);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':id', $socioId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Registrar el cambio en el historial
                $this->registrarCambioEstado($socioId, $nuevoEstado, $motivo);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error en cambiarEstado: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registra un cambio de estado en el historial
     * @param int $socioId ID del socio
     * @param string $nuevoEstado Nuevo estado del socio
     * @param string $motivo Motivo del cambio (opcional)
     * @return bool True si se registró correctamente
     */
    private function registrarCambioEstado($socioId, $nuevoEstado, $motivo = '') {
        $query = "INSERT INTO historial_estados_socios 
                 (socio_id, estado_anterior, estado_nuevo, motivo, fecha_cambio, usuario_id, ip, agente_usuario)
                 VALUES 
                 (:socio_id, :estado_anterior, :estado_nuevo, :motivo, NOW(), :usuario_id, :ip, :agente_usuario)";
        
        try {
            $conn = $this->database->getConnection();
            
            // Verificar si la tabla existe, si no, crearla
            $this->crearTablaHistorialSiNoExiste($conn);
            
            $stmt = $conn->prepare($query);
            
            // Obtener el estado actual del socio
            $this->socioModel->id = $socioId;
            $estadoAnterior = 'desconocido';
            if ($this->socioModel->readOne()) {
                $estadoAnterior = $this->socioModel->estado ?? 'desconocido';
            }
            
            // Obtener información del usuario que realiza el cambio (si está autenticado)
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $agenteUsuario = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->bindParam(":socio_id", $socioId, PDO::PARAM_INT);
            $stmt->bindParam(":estado_anterior", $estadoAnterior);
            $stmt->bindParam(":estado_nuevo", $nuevoEstado);
            $stmt->bindParam(":motivo", $motivo);
            $stmt->bindParam(":usuario_id", $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(":ip", $ip);
            $stmt->bindParam(":agente_usuario", $agenteUsuario);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Error al registrar cambio de estado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea la tabla de historial si no existe
     * @param PDO $conn Conexión a la base de datos
     */
    private function crearTablaHistorialSiNoExiste($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS historial_estados_socios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            socio_id INT NOT NULL,
            estado_anterior VARCHAR(20) NOT NULL,
            estado_nuevo VARCHAR(20) NOT NULL,
            motivo TEXT,
            fecha_cambio DATETIME NOT NULL,
            usuario_id INT,
            ip VARCHAR(45),
            agente_usuario TEXT,
            FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE,
            INDEX (socio_id),
            INDEX (fecha_cambio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
    }
}

// Manejo de las peticiones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener los datos de la petición
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error en el formato JSON: ' . json_last_error_msg(), 400);
        }
        
        // Validar parámetros requeridos
        if (empty($input['accion']) || empty($input['socio_id'])) {
            throw new Exception('Faltan parámetros requeridos: accion, socio_id', 400);
        }
        
        // Validar formato del ID
        if (!is_numeric($input['socio_id'])) {
            throw new Exception('El ID del socio debe ser un número', 400);
        }
        
        $accion = strtolower(trim($input['accion']));
        $socioId = (int)$input['socio_id'];
        $motivo = trim($input['motivo'] ?? '');
        
        // Validar que la acción sea válida
        $accionesPermitidas = ['activar', 'lesionar', 'retirar'];
        if (!in_array($accion, $accionesPermitidas)) {
            throw new Exception('Acción no válida. Las acciones permitidas son: ' . implode(', ', $accionesPermitidas), 400);
        }
        
        // Mapear acciones a estados
        $estados = [
            'activar' => 'activo',
            'lesionar' => 'lesionado',
            'retirar' => 'retirado'
        ];
        
        $nuevoEstado = $estados[$accion] ?? '';
        
        if (empty($nuevoEstado)) {
            throw new Exception('No se pudo determinar el nuevo estado para la acción solicitada', 500);
        }
        
        $controller = new SocioEstadoController();
        
        if ($controller->cambiarEstado($socioId, $nuevoEstado, $motivo)) {
            // Mensajes de éxito personalizados
            $mensajes = [
                'activar' => 'Socio reactivado correctamente',
                'lesionar' => 'Socio marcado como lesionado correctamente',
                'retirar' => 'Socio retirado correctamente'
            ];
            
            $mensaje = $mensajes[$accion] ?? 'Operación completada';
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'socio_id' => $socioId,
                    'nuevo_estado' => $nuevoEstado,
                    'fecha_cambio' => date('Y-m-d H:i:s'),
                    'motivo' => $motivo
                ]
            ]);
        } else {
            throw new Exception('No se pudo actualizar el estado del socio', 500);
        }
        
    } catch (Exception $e) {
        $statusCode = $e->getCode() >= 400 ? $e->getCode() : 500;
        $message = $e->getMessage();
        
        // Log del error para depuración
        error_log("Error en SocioEstadoController: " . $message);
        
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Se esperaba una petición POST.'
    ]);
}
?>
