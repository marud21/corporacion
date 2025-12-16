<?php
// Configuración de encabezados para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir el modelo de Pago
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../includes/database/Database.php';

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Crear instancia del modelo Pago
$pagoModel = new Pago($db);

// Obtener el método de la petición
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    switch ($method) {
        case 'GET':
            // Obtener el ID del socio si se proporciona
            $socio_id = isset($_GET['socio_id']) ? (int)$_GET['socio_id'] : null;
            $filtros = [
                'socio_id' => $socio_id,
                'fecha_desde' => $_GET['fecha_desde'] ?? null,
                'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
                'concepto' => $_GET['concepto'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0
            ];
            
            // Obtener el historial de pagos
            $pagos = $pagoModel->getHistorial($filtros);
            $total = $pagoModel->getTotalRegistros($filtros);
            
            $response = [
                'success' => true,
                'data' => $pagos,
                'total' => $total,
                'filtros' => $filtros
            ];
            break;
            
        case 'POST':
            // Obtener los datos del pago del cuerpo de la petición
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // Debug logging
            error_log('[PAGO_CONTROLLER] Método: POST');
            error_log('[PAGO_CONTROLLER] Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'no definido'));
            error_log('[PAGO_CONTROLLER] Input recibido: ' . substr($input, 0, 200));
            error_log('[PAGO_CONTROLLER] Datos parseados: ' . json_encode($data));
            
            if (!is_array($data)) {
                throw new Exception('Formato JSON inválido. Asegúrate de enviar Content-Type: application/json');
            }
            
            if (empty($data['socio_id']) || empty($data['monto']) || empty($data['concepto'])) {
                throw new Exception('Faltan campos obligatorios: socio_id, monto, concepto');
            }
            
            // Validar monto
            $monto = (float)$data['monto'];
            if ($monto <= 0) {
                throw new Exception('El monto debe ser mayor a cero');
            }
            
            // Crear el pago
            $pago_id = $pagoModel->create([
                'socio_id' => (int)$data['socio_id'],
                'monto' => $monto,
                'concepto' => $data['concepto'],
                'fecha' => $data['fecha'] ?? date('Y-m-d H:i:s'),
                'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
                'referencia' => $data['referencia'] ?? null,
                'observaciones' => $data['observaciones'] ?? null
            ]);
            
            $response = [
                'success' => true,
                'message' => 'Pago registrado correctamente',
                'pago_id' => $pago_id
            ];
            break;
            
        default:
            http_response_code(405);
            $response['message'] = 'Método no permitido';
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('[PAGO_CONTROLLER] Error capturado: ' . $e->getMessage());
    error_log('[PAGO_CONTROLLER] Stack: ' . $e->getTraceAsString());
}

// Enviar la respuesta como JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
